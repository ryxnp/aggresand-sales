<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#soa.php';

/* =========================================================
   CRUD HANDLING (UPDATE BILLING DATE ONLY)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $soa_id = (int)($_POST['soa_id'] ?? 0);
    $billing_date = $_POST['billing_date'] ?? '';

    try {

        if ($action !== 'update') {
            throw new Exception('Invalid action');
        }

        if ($soa_id <= 0) {
            throw new Exception('Invalid SOA ID');
        }

        if (!$billing_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $billing_date)) {
            throw new Exception('Invalid billing date');
        }

        $oldStmt = $conn->prepare("
            SELECT * FROM statement_of_account
            WHERE soa_id = :id AND is_deleted = 0
        ");
        $oldStmt->execute([':id' => $soa_id]);
        $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldData) {
            throw new Exception('SOA not found');
        }

        $audit = audit_on_update($admin);

        $stmt = $conn->prepare("
            UPDATE statement_of_account SET
                billing_date = :billing_date,
                date_edited  = :edited,
                edited_by    = :edited_by
            WHERE soa_id = :id
        ");

        $stmt->execute([
            ':billing_date' => $billing_date,
            ':edited'       => $audit['date_edited'],
            ':edited_by'    => $audit['edited_by'],
            ':id'           => $soa_id
        ]);

        audit_log(
            'statement_of_account',
            $soa_id,
            'UPDATE',
            $oldData,
            ['billing_date' => $billing_date],
            $admin
        );

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Billing date updated'];

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* =========================================================
   LIST + SUMMARY
   ========================================================= */

require_once __DIR__ . '/../helpers/alerts.php';

$q = trim($_GET['q'] ?? '');

$sql = "
    SELECT
        s.soa_id,
        s.soa_no,
        s.billing_date,
        s.terms,
        s.date_edited,
        co.company_name,
        si.site_name,
        COUNT(d.del_id) AS delivery_count,
        COALESCE(SUM(d.quantity * d.unit_price), 0) AS total_amount
    FROM statement_of_account s
    JOIN company co ON s.company_id = co.company_id
    JOIN site si ON s.site_id = si.site_id
    LEFT JOIN delivery d ON d.soa_id = s.soa_id AND d.is_deleted = 0
    WHERE s.is_deleted = 0
";

$params = [];

if ($q !== '') {
    $sql .= " AND (s.soa_no LIKE :q OR co.company_name LIKE :q OR si.site_name LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

$sql .= "
    GROUP BY s.soa_id
    ORDER BY s.date_edited DESC, s.date_created DESC
    LIMIT 15
";

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">

    <h2 class="mb-4">Statement of Account</h2>

    <?php if (!empty($_SESSION['alert'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="row">

        <!-- FORM -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="soa-form-title">Edit Billing Date</div>
                <div class="card-body">
                    <form id="soa-form" method="POST" action="pages/soa.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="soa_id" id="soa_id">

                        <div class="mb-3">
                            <label class="form-label">SOA No</label>
                            <input type="text" id="soa_no" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" id="company_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Site</label>
                            <input type="text" id="site_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Billing Date</label>
                            <input type="date" name="billing_date" id="billing_date"
                                   class="form-control" required>
                        </div>

                        <button class="btn btn-primary" id="soa-submit-btn">Update Billing Date</button>
                        <button type="button" class="btn btn-secondary d-none" id="soa-cancel-btn">Cancel</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex gap-2 align-items-center">
                    <span class="me-auto">Recent SOAs</span>

                    <form class="d-flex gap-2 soa-filter-form">
                        <input type="text" name="q"
                               value="<?= htmlspecialchars($q) ?>"
                               class="form-control form-control-sm"
                               placeholder="Search SOA / Company / Site"
                               style="width:260px;">
                        <button class="btn btn-sm btn-outline-primary">Apply</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SOA No</th>
                                <th>Company</th>
                                <th>Site</th>
                                <th>Billing Date</th>
                                <th>Deliveries</th>
                                <th>Total Amount</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7" class="text-center">No records found</td></tr>
                        <?php else:
                            foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['soa_no']) ?></td>
                                    <td><?= htmlspecialchars($r['company_name']) ?></td>
                                    <td><?= htmlspecialchars($r['site_name']) ?></td>
                                    <td><?= htmlspecialchars($r['billing_date']) ?></td>
                                    <td><?= (int)$r['delivery_count'] ?></td>
                                    <td><?= number_format((float)$r['total_amount'], 2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary soa-btn-edit"
                                            data-id="<?= (int)$r['soa_id'] ?>"
                                            data-soa="<?= htmlspecialchars($r['soa_no'], ENT_QUOTES) ?>"
                                            data-company="<?= htmlspecialchars($r['company_name'], ENT_QUOTES) ?>"
                                            data-site="<?= htmlspecialchars($r['site_name'], ENT_QUOTES) ?>"
                                            data-billing="<?= htmlspecialchars($r['billing_date']) ?>">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>
