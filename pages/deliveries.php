<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/alerts.php';

$admin = $_SESSION['admin_id'] ?? null;

/* Keep DR search value when redirecting after POST */
$dr_keep = trim($_POST['dr_no'] ?? ($_POST['dr'] ?? ($_GET['dr'] ?? '')));
$redirectUrl = '/main.php?page=deliveries.php'
    . ($dr_keep !== '' ? ('&dr=' . urlencode($dr_keep)) : '');

/* =========================
   PAYMENT CREATE (POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_payment') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    try {
        $dr_no     = trim($_POST['dr_no'] ?? '');
        $amount    = (float)($_POST['amount_paid'] ?? 0);
        $date_paid = trim($_POST['date_paid'] ?? '');
        $si_no     = trim($_POST['si_no'] ?? '');
        $check_no  = trim($_POST['check_no'] ?? '');

        if ($dr_no === '' || $amount <= 0 || $date_paid === '') {
            throw new Exception('Invalid payment data');
        }

        // DR total
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(quantity * unit_price),0)
            FROM delivery
            WHERE dr_no = :dr AND is_deleted = 0
        ");
        $stmt->execute([':dr' => $dr_no]);
        $drTotal = (float)$stmt->fetchColumn();

        if ($drTotal <= 0) {
            throw new Exception('Invalid DR');
        }

        // paid so far
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(amount_paid),0)
            FROM delivery_payments
            WHERE dr_no = :dr AND is_deleted = 0
        ");
        $stmt->execute([':dr' => $dr_no]);
        $paidSoFar = (float)$stmt->fetchColumn();

        if (($paidSoFar + $amount) > $drTotal) {
            throw new Exception('Payment exceeds balance');
        }

        $newPaid = $paidSoFar + $amount;
        $status = $newPaid >= $drTotal ? 'PAID' : ($newPaid > 0 ? 'PARTIAL' : 'UNPAID');

        $audit = audit_on_create($admin);

        $stmt = $conn->prepare("
            INSERT INTO delivery_payments
                (dr_no, amount_paid, date_paid, si_no, check_no,
                 status, created_by, date_created, is_deleted)
            VALUES
                (:dr, :amt, :dt, :si, :chk,
                 :st, :by, :dc, 0)
        ");
        $stmt->execute([
            ':dr'  => $dr_no,
            ':amt' => $amount,
            ':dt'  => $date_paid,
            ':si'  => $si_no,
            ':chk' => $check_no,
            ':st'  => $status,
            ':by'  => $audit['created_by'],
            ':dc'  => $audit['date_created'],
        ]);

        audit_log('delivery_payments', $conn->lastInsertId(), 'CREATE', null, $_POST, $admin);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Payment added'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* =========================
   PAYMENT UPDATE (POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_payment') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    try {
        $payment_id = (int)($_POST['payment_id'] ?? 0);
        $dr_no      = trim($_POST['dr_no'] ?? '');
        $amount     = (float)($_POST['amount_paid'] ?? 0);
        $date_paid  = trim($_POST['date_paid'] ?? '');
        $si_no      = trim($_POST['si_no'] ?? '');
        $check_no   = trim($_POST['check_no'] ?? '');

        if ($payment_id <= 0 || $dr_no === '' || $amount <= 0 || $date_paid === '') {
            throw new Exception('Invalid payment data');
        }

        // DR total
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(quantity * unit_price),0)
            FROM delivery
            WHERE dr_no = :dr AND is_deleted = 0
        ");
        $stmt->execute([':dr' => $dr_no]);
        $drTotal = (float)$stmt->fetchColumn();

        if ($drTotal <= 0) {
            throw new Exception('Invalid DR');
        }

        // paid so far excluding this payment
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(amount_paid),0)
            FROM delivery_payments
            WHERE dr_no = :dr AND is_deleted = 0 AND payment_id <> :pid
        ");
        $stmt->execute([':dr' => $dr_no, ':pid' => $payment_id]);
        $paidSoFar = (float)$stmt->fetchColumn();

        if (($paidSoFar + $amount) > $drTotal) {
            throw new Exception('Payment exceeds balance');
        }

        $newPaid = $paidSoFar + $amount;
        $status = $newPaid >= $drTotal ? 'PAID' : ($newPaid > 0 ? 'PARTIAL' : 'UNPAID');

        $stmt = $conn->prepare("
            UPDATE delivery_payments
            SET amount_paid = :amt,
                date_paid   = :dt,
                si_no       = :si,
                check_no    = :chk,
                status      = :st
            WHERE payment_id = :pid AND is_deleted = 0
        ");
        $stmt->execute([
            ':amt' => $amount,
            ':dt'  => $date_paid,
            ':si'  => $si_no,
            ':chk' => $check_no,
            ':st'  => $status,
            ':pid' => $payment_id
        ]);

        audit_log('delivery_payments', $payment_id, 'UPDATE', null, $_POST, $admin);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Payment updated'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* =========================
   PAYMENT DELETE (POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_payment') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    try {
        $payment_id = (int)($_POST['payment_id'] ?? 0);
        if ($payment_id <= 0) {
            throw new Exception('Invalid payment');
        }

        $stmt = $conn->prepare("
            UPDATE delivery_payments
            SET is_deleted = 1
            WHERE payment_id = :pid
        ");
        $stmt->execute([':pid' => $payment_id]);

        audit_log('delivery_payments', $payment_id, 'DELETE', null, $_POST, $admin);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Payment deleted'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* =========================
   PAGE DATA
========================= */

$dr = trim($_GET['dr'] ?? '');
$rows = [];
$paymentsByDr = [];

/* IMPORTANT: do not show ANY results until user searches */
if ($dr !== '') {

    $sql = "
        SELECT
            d.dr_no,
            d.delivery_date,
            c.company_name,
            s.billing_date,
            t.plate_no,
            d.material,
            d.quantity,
            d.unit_price,
            (d.quantity * d.unit_price) AS line_total,

            (SELECT SUM(quantity * unit_price)
             FROM delivery WHERE dr_no = d.dr_no AND is_deleted = 0) AS dr_total,

            (SELECT IFNULL(SUM(amount_paid),0)
             FROM delivery_payments WHERE dr_no = d.dr_no AND is_deleted = 0) AS total_paid

        FROM delivery d
        LEFT JOIN company c ON c.company_id = d.company_id
        LEFT JOIN statement_of_account s ON s.soa_id = d.soa_id
        LEFT JOIN truck t ON t.truck_id = d.truck_id
        WHERE d.is_deleted = 0
          AND d.dr_no = :dr
        ORDER BY d.delivery_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':dr' => $dr]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Payment history by DR for the visible results */
    if ($rows) {
        $drNos = array_unique(array_column($rows, 'dr_no'));
        $in = implode(',', array_fill(0, count($drNos), '?'));

        $stmt = $conn->prepare("
            SELECT dr_no, payment_id, date_paid, amount_paid, si_no, check_no, status
            FROM delivery_payments
            WHERE is_deleted = 0 AND dr_no IN ($in)
            ORDER BY date_paid DESC, payment_id DESC
        ");
        $stmt->execute($drNos);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $paymentsByDr[$p['dr_no']][] = $p;
        }
    }
}
?>

<div class="container-fluid">
    <h2 class="mb-4">Deliveries</h2>

    <?php if (!empty($_SESSION['alert'])) { ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php } ?>

    <div class="row">

        <!-- LEFT -->
        <div class="col-lg-4 mb-4">

            <!-- SEARCH -->
            <div class="card mb-3">
                <div class="card-header">Search Delivery</div>
                <div class="card-body">
                    <form class="deliveries-filter-form">
                        <div class="mb-3">
                            <label class="form-label">DR No</label>
                            <input name="dr"
                                   class="form-control"
                                   placeholder="Enter DR No"
                                   value="<?= htmlspecialchars($dr) ?>">
                        </div>
                        <button class="btn btn-primary w-100">Search</button>
                    </form>
                </div>
            </div>

            <!-- ADD PAYMENT -->
            <div class="card mb-3">
                <div class="card-header">Add Payment</div>
                <div class="card-body" id="payment-form-container">
                    <p class="text-muted mb-0">Select a delivery.</p>
                </div>
            </div>

            <!-- DR SUMMARY -->
            <div class="card">
                <div class="card-header">DR Summary</div>
                <div class="card-body text-end" id="dr-summary">
                    <div><strong>Total:</strong> ₱0.00</div>
                    <div class="text-success"><strong>Paid:</strong> ₱0.00</div>
                    <hr class="my-2">
                    <div class="text-danger fs-5">
                        <strong>Balance:</strong> ₱0.00
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-8 mb-4">

            <!-- DELIVERY INFO -->
            <div class="card mb-3">
                <div class="card-header">Delivery Information</div>
                <div class="card-body" id="delivery-info">
                    <p class="text-muted mb-0">Select a delivery.</p>
                </div>
            </div>

            <!-- DELIVERY RESULTS -->
            <div class="card mb-3">
                <div class="card-header">Delivery Results</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DR No</th>
                                <th>Company</th>
                                <th>Material</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows) { ?>
                            <tr>
                                <td colspan="6" class="text-center">No records found.</td>
                            </tr>
                        <?php } else {
                            foreach ($rows as $r):
                                $balance = (float)$r['dr_total'] - (float)$r['total_paid'];
                        ?>
                            <tr class="delivery-row"
                                data-dr="<?= htmlspecialchars($r['dr_no'], ENT_QUOTES) ?>"
                                data-company="<?= htmlspecialchars($r['company_name'], ENT_QUOTES) ?>"
                                data-delivery="<?= htmlspecialchars($r['delivery_date'], ENT_QUOTES) ?>"
                                data-billing="<?= htmlspecialchars($r['billing_date'], ENT_QUOTES) ?>"
                                data-plate="<?= htmlspecialchars($r['plate_no'], ENT_QUOTES) ?>"
                                data-material="<?= htmlspecialchars($r['material'], ENT_QUOTES) ?>"
                                data-qty="<?= number_format((float)$r['quantity'],2) ?>"
                                data-price="<?= number_format((float)$r['unit_price'],2) ?>"
                                data-line-total="<?= number_format((float)$r['line_total'],2) ?>"
                                data-dr-total="<?= number_format((float)$r['dr_total'],2) ?>"
                                data-paid="<?= number_format((float)$r['total_paid'],2) ?>"
                                data-balance="<?= number_format($balance,2) ?>">
                                <td><?= htmlspecialchars($r['dr_no']) ?></td>
                                <td><?= htmlspecialchars($r['company_name']) ?></td>
                                <td><?= htmlspecialchars($r['material']) ?></td>
                                <td><?= number_format((float)$r['quantity'],2) ?></td>
                                <td><?= number_format((float)$r['unit_price'],2) ?></td>
                                <td><strong><?= number_format((float)$r['line_total'],2) ?></strong></td>
                            </tr>
                        <?php endforeach; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAYMENT HISTORY -->
            <div class="card">
                <div class="card-header">Payment History</div>
                <div class="card-body table-responsive" id="payment-history">
                    <p class="text-muted mb-0">Select a delivery.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- pass payments to JS -->
<div id="payments-data"
     data-payments='<?= htmlspecialchars(json_encode($paymentsByDr), ENT_QUOTES) ?>'></div>
