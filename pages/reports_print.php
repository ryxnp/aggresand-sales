<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$termsValue = trim((string)($soa['terms'] ?? ''));

if ($termsValue === '*') {
    $termsText = 'Cash payment is not accepted. ' .
                 'Make all check payables to ALPHASAND AGGREGATES TRADING';
} else {
    $termsText = $termsValue . ' Days upon presentation of SOA. ' .
                 'Make all check payables to ALPHASAND AGGREGATES TRADING';
}

/* =========================
   VALIDATION
========================= */
$soa_id = (int)($_GET['soa_id'] ?? 0);
if ($soa_id <= 0) {
    die('Invalid SOA');
}

/* =========================
   FETCH SOA HEADER
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
    WHERE s.soa_id = :id
      AND s.is_deleted = 0
    LIMIT 1
");
$soaStmt->execute([':id' => $soa_id]);
$soa = $soaStmt->fetch(PDO::FETCH_ASSOC);

if (!$soa) {
    die('SOA not found');
}

/* =========================
   FETCH DELIVERIES
========================= */
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
$stmt->execute([':soa_id' => $soa_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRows = count($rows);
if ($totalRows === 0) {
    die('No deliveries found');
}

/* =========================
   TOTALS
========================= */
$rowsPerPage = 24;
$totalPages  = max(1, ceil($totalRows / $rowsPerPage));
$pageClass   = $totalRows > 20 ? 'page dense-table' : 'page';

$totalQty = 0;
$totalAmt = 0;
foreach ($rows as $r) {
    $totalQty += (float)$r['quantity'];
    $totalAmt += (float)$r['quantity'] * (float)$r['unit_price'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account <?= htmlspecialchars($soa['soa_no']) ?></title>
    <style>
        @import url('../css/print.css');
    </style>
</head>
<body>

<?php
$rowIndex  = 0;
$pageIndex = 0;

foreach ($rows as $r) {

    if ($rowIndex % $rowsPerPage === 0) {
        $pageIndex++;

        if ($rowIndex > 0) {
            echo "</tbody></table></div><div class='page-break'></div>";
        }

        echo "<div class='{$pageClass}'>";
        ?>

        <!-- HEADER -->
        <img src="../assets/headerv2.png" class="header-image">

        <div class="page-meta">
            <div class="billing-date">
                <?= date('F d, Y', strtotime($soa['billing_date'])) ?>
            </div>
            <div class="page-number">
                Page <?= $pageIndex ?> of <?= $totalPages ?>
            </div>
        </div>

        <table class="soa-header-table">
        <tr>
            <td>
                <span class="label">Company Name:</span>
                <span class="value"><?= htmlspecialchars($soa['company_name']) ?></span>
            </td>
            <td class="right">
                <span class="label">Statement of Account No:</span>
                <span class="value"><?= htmlspecialchars($soa['soa_no']) ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Project Site:</span>
                <span class="value"><?= htmlspecialchars($soa['site_name']) ?></span>
            </td>
            <td class="right">
                <span class="label">PO Number:</span>
                <span class="value">*</span>
            </td>
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

        <?php
    }

    $amount = (float)$r['quantity'] * (float)$r['unit_price'];
    ?>

    <tr>
        <td><?= htmlspecialchars($r['delivery_date']) ?></td>
        <td><?= htmlspecialchars($r['dr_no']) ?></td>
        <td><?= htmlspecialchars($r['plate_no']) ?></td>
        <td><?= htmlspecialchars($r['material']) ?></td>
        <td class="text-end"><?= number_format($r['quantity'], 2) ?></td>
        <td class="text-end"><?= number_format($r['unit_price'], 2) ?></td>
        <td class="text-end"><?= number_format($amount, 2) ?></td>
    </tr>

    <?php
    $rowIndex++;
}

/* =========================
   FINAL PAGE CONTENT
========================= */
echo "</tbody></table>";
?>

<div class="totals-section">
    <div><strong>Total Quantity:</strong> <?= number_format($totalQty, 2) ?></div>
    <div><strong>Total Amount:</strong> ₱<?= number_format($totalAmt, 2) ?></div>
    <div><strong>Total DR Count:</strong> <?= $totalRows ?></div>

    <div class="terms-block">
    <strong>Terms of Payment:</strong><br>

    <?php
    $termsValue = trim((string)($soa['terms'] ?? ''));

    if ($termsValue === '*'):
    ?>
        <span class="terms-highlight">
            Cash payment is not accepted. Make all check payables to <strong>ALPHASAND AGGREGATES TRADING</strong>
        </span>
    <?php else: ?>
        <strong><?= htmlspecialchars($termsValue) ?></strong>
        Days upon presentation of SOA.
        Make all check payables to <strong>ALPHASAND AGGREGATES TRADING</strong>
    <?php endif; ?>
</div>
</div>

<!-- FOOTER (FINAL PAGE ONLY) -->
<div class="footer">
    <table class="footer-table">
        <tr>
            <td>
                <strong>Prepared By:</strong><br><br>
                <span class="sig-name"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span><br>
                <span class="sig-role">Sales and Operation Officer</span>
            </td>
            <td>
                <strong>Checked By:</strong><br><br>
                <span class="sig-name">Ma. Thricia Agustin</span><br>
                <span class="sig-role">Accounting & Admin Officer</span>
            </td>
            <td>
                <strong>Approved By:</strong><br><br>
                <span class="sig-name">Analyn Buenviaje</span><br>
                <span class="sig-role">General Manager</span>
            </td>
            <td>
                <strong>Received By:</strong><br><br>
                <span class="sig-name">___________________________</span><br>
                <span class="sig-role">Signature over Printed Name</span>
            </td>
        </tr>
    </table>
</div>

</div>

<script>
    window.print();
</script>

</body>
</html>
