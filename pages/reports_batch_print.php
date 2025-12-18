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

        <table class="header-table">
            <tr>
                <td><strong>Company:</strong> <?= htmlspecialchars($soa['company_name']) ?></td>
                <td><strong>SOA No:</strong> <?= htmlspecialchars($soa['soa_no']) ?></td>
                <td><strong>Billing Date:</strong> <?= htmlspecialchars($soa['billing_date']) ?></td>
            </tr>
            <tr>
                <td><strong>Site:</strong> <?= htmlspecialchars($soa['site_name']) ?></td>
                <td colspan="2">
                    <strong>Terms:</strong>
                    <?php if ($soa['terms'] === '*'): ?>
                        <strong>No Cash Payment will be accepted</strong>
                    <?php else: ?>
                        <?= htmlspecialchars($soa['terms']) ?> days
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <table class="soa-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>DR No</th>
                    <th>Plate</th>
                    <th>Material</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Amount</th>
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
                    <td class="right"><?= number_format($r['quantity'], 2) ?></td>
                    <td class="right"><?= number_format($r['unit_price'], 2) ?></td>
                    <td class="right"><?= number_format($rowAmt, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <strong>Total Qty:</strong> <?= number_format($qty, 2) ?><br>
            <strong>Total Amount:</strong> ₱<?= number_format($amt, 2) ?>
        </div>
        <div class="footer">
    <table style="width:100%; font-size:11px;">
        <tr>
            <td>
                <strong>Prepared By:</strong><br><br>
                __________________________<br>
                <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
            </td>
            <td>
                <strong>Checked By:</strong><br><br>
                __________________________
            </td>
            <td>
                <strong>Approved By:</strong><br><br>
                __________________________
            </td>
            <td>
                <strong>Date Submitted:</strong><br><br>
                __________________________
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
@page { size: A4; margin: 10mm; }
body { font-family: Arial; font-size: 11px; margin: 0; }
.page {
    width: 190mm;
    min-height: 277mm;
    position: relative; /* 🔴 REQUIRED */
}
.page-break { page-break-before: always; }
.header-image { width: 100%; margin-bottom: 5px; }
.header-table { width: 100%; font-size: 11px; margin-bottom: 6px; }
.soa-table { width: 100%; border-collapse: collapse; }
.soa-table th, .soa-table td { border: 1px solid #000; padding: 4px; font-size: 10px; }
.soa-table th { background: #f0f0f0; }
.right { text-align: right; }
.totals { margin-top: 10px; }
.footer {
    position: absolute;
    bottom: 10mm;
    left: 0;
    width: 100%;
}
</style>
</head>
<body>

<?php
$first = true;
foreach ($soas as $soa) {
    printSOA($conn, $soa, !$first);
    $first = false;
}
?>

<script>window.print();</script>
</body>
</html>
