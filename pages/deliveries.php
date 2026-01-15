<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/alerts.php';
require_once __DIR__ . '/../helpers/date.php';

$admin = $_SESSION['admin_id'] ?? null;

/* =========================================================
   SAVE DR STATUS (POST)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_status') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: /main.php?page=deliveries.php");
        exit;
    }

    try {
        $dr_no  = trim($_POST['dr_no'] ?? '');
        $status = $_POST['status'] ?? 'UNPAID';

        $date_paid = trim($_POST['date_paid'] ?? '');
        if ($date_paid === '') {
            $date_paid = date('Y-m-d');
        }

        $si_no    = trim($_POST['si_no'] ?? '');
        $check_no = trim($_POST['check_no'] ?? '');

        if ($dr_no === '' || !in_array($status, ['UNPAID','PARTIAL','PAID'], true)) {
            throw new Exception('Invalid data');
        }

        // compute amount_paid from delivery
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(quantity * unit_price),0)
            FROM delivery
            WHERE dr_no = :dr AND is_deleted = 0
        ");
        $stmt->execute([':dr' => $dr_no]);
        $amount_paid = (float)$stmt->fetchColumn();

        if ($amount_paid <= 0) {
            throw new Exception('Invalid DR');
        }

        // UPSERT
        $stmt = $conn->prepare("
            INSERT INTO delivery_payments
                (dr_no, si_no, check_no, date_paid, amount_paid,
                 status, created_by, date_created, is_deleted)
            VALUES
                (:dr, :si, :chk, :dt, :amt,
                 :st, :by, NOW(), 0)
            ON DUPLICATE KEY UPDATE
                si_no       = VALUES(si_no),
                check_no    = VALUES(check_no),
                date_paid   = VALUES(date_paid),
                amount_paid = VALUES(amount_paid),
                status      = VALUES(status),
                edited_by   = :by
        ");

        $stmt->execute([
            ':dr'  => $dr_no,
            ':si'  => $si_no,
            ':chk' => $check_no,
            ':dt'  => $date_paid,
            ':amt' => $amount_paid,
            ':st'  => $status,
            ':by'  => $admin
        ]);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'DR status saved'];

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: /main.php?page=deliveries.php&dr=" . urlencode($dr_no));
    exit;
}

/* =========================================================
   LIST + FILTER (GET)
   ========================================================= */

$dr = trim($_GET['dr'] ?? '');

$sql = "
    SELECT
        d.dr_no,
        d.delivery_date,
        d.material,
        d.quantity,
        d.unit_price,
        (d.quantity * d.unit_price) AS line_total,

        c.company_name,
        st.site_name,
        s.billing_date,
        t.plate_no,

        dp.status      AS pay_status,
        dp.date_paid   AS pay_date,
        dp.si_no       AS pay_si,
        dp.check_no    AS pay_check,
        dp.amount_paid AS pay_amount

    FROM delivery d
    LEFT JOIN company c ON c.company_id = d.company_id
    LEFT JOIN statement_of_account s ON s.soa_id = d.soa_id
    LEFT JOIN site st ON st.site_id = s.site_id
    LEFT JOIN truck t ON t.truck_id = d.truck_id
    LEFT JOIN delivery_payments dp
           ON dp.dr_no = d.dr_no AND dp.is_deleted = 0
    WHERE d.is_deleted = 0
";

$params = [];

if ($dr !== '') {
    $sql .= " AND d.dr_no LIKE :dr";
    $params[':dr'] = '%' . $dr . '%';
}

$sql .= " ORDER BY d.delivery_date DESC";

if ($dr === '') {
    $sql .= " LIMIT 10";
}

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Deliveries</h2>

    <?php if (!empty($_SESSION['alert'])) { ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php } ?>

    <div class="row">

        <!-- LEFT -->
        <div class="col-lg-4 mb-4">

            <!-- FORM -->
            <div class="card">
                <div class="card-header">DR Status</div>
                <div class="card-body">
                    <form method="POST" action="pages/deliveries.php">
                        <input type="hidden" name="action" value="save_status">
                        <input type="hidden" name="dr_no" id="form_dr_no_hidden">

                        <div class="mb-2">
                            <label class="form-label">DR No</label>
                            <input id="form_dr_no" class="form-control bg-light text-muted" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Amount Paid</label>
                            <input id="form_amount" class="form-control bg-light text-muted" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="form_status" class="form-select">
                                <option value="UNPAID">UNPAID</option>
                                <option value="PARTIAL">PARTIAL</option>
                                <option value="PAID">PAID</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Date Paid</label>
                            <input type="date" name="date_paid" id="form_date_paid" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">SI No</label>
                            <input name="si_no" id="form_si_no" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Check No</label>
                            <input name="check_no" id="form_check_no" class="form-control">
                        </div>

                        <button class="btn btn-primary w-100" id="form_save_btn" disabled>
                            Save
                        </button>
                    </form>
                </div>
            </div>

            <!-- SUMMARY -->
            <div class="card mt-3">
                <div class="card-header">DR Summary</div>
                <div class="card-body" id="dr-status-info">
                    <p class="text-muted mb-0">Select a DR to view summary.</p>
                </div>
            </div>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex gap-2 align-items-center">
                        <span class="me-auto">
                            <?= $dr === '' ? 'Recent Deliveries' : 'Search Results' ?>
                        </span>

                        <form class="d-flex gap-2 deliveries-filter-form">
                            <input name="dr"
                                   value="<?= htmlspecialchars($dr) ?>"
                                   class="form-control form-control-sm"
                                   placeholder="Search DR No">
                            <button class="btn btn-sm btn-outline-primary">Apply</button>
                        </form>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered mb-0" id="deliveries-table">
                        <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>Site</th>
                            <th>Delivery Date</th>
                            <th>DR No</th>
                            <th>Plate No</th>
                            <th>Material</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows) { ?>
                            <tr><td colspan="9" class="text-center">No records found.</td></tr>
                        <?php } else foreach ($rows as $r) { ?>
                            <tr class="deliveries-row"
                                data-dr="<?= htmlspecialchars($r['dr_no'], ENT_QUOTES) ?>"
                                data-total="<?= number_format((float)$r['line_total'], 2, '.', '') ?>"
                                data-status="<?= htmlspecialchars($r['pay_status'] ?? 'UNPAID', ENT_QUOTES) ?>"
                                data-date="<?= formatDateMDY($r['pay_date'] ?? '', ENT_QUOTES) ?>"
                                data-si="<?= htmlspecialchars($r['pay_si'] ?? '', ENT_QUOTES) ?>"
                                data-check="<?= htmlspecialchars($r['pay_check'] ?? '', ENT_QUOTES) ?>"
                                data-paid="<?= number_format((float)($r['pay_amount'] ?? 0), 2, '.', '') ?>"
                                data-billing="<?= formatDateMDY($r['billing_date'] ?? '', ENT_QUOTES) ?>">
                                <td><?= htmlspecialchars($r['company_name']) ?></td>
                                <td><?= htmlspecialchars($r['site_name'] ?? '-') ?></td>
                                <td><?= formatDateMDY($r['delivery_date']) ?></td>
                                <td><?= htmlspecialchars($r['dr_no']) ?></td>
                                <td><?= htmlspecialchars($r['plate_no']) ?></td>
                                <td><?= htmlspecialchars($r['material']) ?></td>
                                <td><?= number_format($r['quantity'], 2) ?></td>
                                <td><?= number_format($r['unit_price'], 2) ?></td>
                                <td><strong><?= number_format($r['line_total'], 2) ?></strong></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
