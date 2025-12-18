<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* =========================
   VALIDATION
========================= */
$billing_date = $_GET['billing_date'] ?? '';
if ($billing_date === '') {
    die('Billing date is required');
}

/* =========================
   FETCH SOAs
========================= */
$soaStmt = $conn->prepare("
    SELECT
        s.soa_id,
        s.soa_no,
        s.billing_date,
        s.terms,
        c.company_name,
        si.site_name
    FROM statement_of_account s
    JOIN company c ON s.company_id = c.company_id
    JOIN site si ON s.site_id = si.site_id
    WHERE s.status = 'finalized'
      AND s.is_deleted = 0
      AND s.billing_date = :billing_date
    ORDER BY s.soa_no
");
$soaStmt->execute([':billing_date' => $billing_date]);
$soas = $soaStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$soas) {
    die('No SOAs found');
}

/* =========================
   HELPER
========================= */
function printSOA(PDO $conn, array $soa, bool $pageBreak)
{
    $stmt = $conn->prepare("
        SELECT
            d.delivery_date,
            d.dr_no,
            d.material,
            d.quantity,
            d.unit_price,
            t.plate_no
        FROM delivery d
        LEFT JOIN truck t ON d.truck_id = t.truck_id
        WHERE d.soa_id = :soa_id
          AND d.is_deleted = 0
        ORDER BY d.delivery_date, d.dr_no
    ");
    $stmt->execute([':soa_id' => $soa['soa_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $qty = 0;
    $amt = 0;

    foreach ($rows as $r) {
        $qty += (float)$r['quantity'];
        $amt += (float)$r['quantity'] * (float)$r['unit_price'];
    }

    if ($pageBreak) echo '<div class="page-break"></div>';
?>
    <div class="page">
        <img src="../assets/header.png" class="header-image">

        <table class="soa-header-table">
            <tr>
                <td><strong>Company Name:</strong> <?= htmlspecialchars($soa['company_name']) ?></td>
                <td><strong>Statement of Account No:</strong> <?= htmlspecialchars($soa['soa_no']) ?></td>
            </tr>
            <tr>
                <td><strong>Project Site:</strong> <?= htmlspecialchars($soa['site_name']) ?></td>
                <td><strong>PO Number:</strong> *</td>
            </tr>
            <tr>
                <td><strong>Billing Date:</strong> <?= htmlspecialchars($soa['billing_date']) ?></td>
            </tr>
        </table>

        <table class="soa-table">
            <thead class="soa-header">
                <tr>
                    <th>Date</th>
                    <th>DR No.</th>
                    <th>Plate No.</th>
                    <th>Materials</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $rowAmt = $r['quantity'] * $r['unit_price'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($r['delivery_date']) ?></td>
                        <td><?= htmlspecialchars($r['dr_no']) ?></td>
                        <td><?= htmlspecialchars($r['plate_no']) ?></td>
                        <td><?= htmlspecialchars($r['material']) ?></td>
                        <td style='text-align:right'><?= number_format($r['quantity'], 2) ?></td>
                        <td style='text-align:right'><?= number_format($r['unit_price'], 2) ?></td>
                        <td style='text-align:right'><?= number_format($rowAmt, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <div><strong>Total Quantity:</strong> <?= number_format($qty, 2) ?></div>
            <div><strong>Total Amount:</strong> ₱<?= number_format($amt, 2) ?></div>
            <div><strong>Total DR Count:</strong> <?= count($rows) ?></div>

            <div class="terms-block">
                <strong>Terms of Payment:</strong><br>
                <?php if ($soa['terms'] === '*'): ?>
                    <span class="terms-highlight">
                        NO CASH PAYMENT WILL BE ACCEPTED
                    </span>
                <?php else: ?>
                    <?= htmlspecialchars($soa['terms']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    <strong>Prepared By:</strong><br><br><br>
                    <span class="sig-name"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span><br>
                    <span class="sig-role">Sales and Operation Officer</span>
                </td>
                <td>
                    <strong>Checked By:</strong><br><br><br>
                    <span class="sig-name">Ma. Christa Agustin</span><br>
                    <span class="sig-role">Accounting & Admin Officer</span>
                </td>
                <td>
                    <strong>Approved By:</strong><br><br><br>
                    <span class="sig-name">Analyn Buenviaje</span><br>
                    <span class="sig-role">General Manager</span>
                </td>
                <td>
                    <strong>Received By:</strong><br><br><br>
                    <span class="sig-name">Customer's Name</span><br>
                    <span class="sig-role">&nbsp;</span>
                </td>
            </tr>
        </table>
    </div>
    </div>

<?php
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>SOA Batch Print</title>
    <style>
        @import url('../css/print.css');
    </style>
</head>

<body>
    <div class="print-date">
        <?= date('d–F–Y') ?>
    </div>
    <?php
    $first = true;
    foreach ($soas as $soa) {
        printSOA($conn, $soa, !$first);
        $first = false;
    }
    ?>

    <script>
        window.print();
    </script>
</body>

</html>