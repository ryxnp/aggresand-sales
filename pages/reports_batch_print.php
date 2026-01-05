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
   FETCH FINALIZED SOAs
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
    WHERE s.is_deleted = 0
      AND s.billing_date = :billing_date
    ORDER BY s.soa_no ASC
");
$soaStmt->execute([':billing_date' => $billing_date]);
$soas = $soaStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PRINT SINGLE SOA
========================= */
function printSOA(PDO $conn, array $soa, bool $pageBreak)
{
    $rowsPerPage = 24;

    /* =========================
       FETCH DRs
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
    $stmt->execute([':soa_id' => $soa['soa_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRows  = count($rows);
    $totalPages = max(1, ceil(max($totalRows, 1) / $rowsPerPage));

    /* totals */
    $totalQty = 0;
    $totalAmt = 0;
    foreach ($rows as $r) {
        $totalQty += (float)$r['quantity'];
        $totalAmt += (float)$r['quantity'] * (float)$r['unit_price'];
    }

    /* force one iteration if NO DR */
    if ($totalRows === 0) {
        $rows = [null];
    }

    if ($pageBreak) {
        echo '<div class="page-break"></div>';
    }

    $rowIndex  = 0;
    $pageIndex = 0;

    foreach ($rows as $r) {

        /* =========================
           NEW PAGE
        ========================= */
        if ($rowIndex % $rowsPerPage === 0) {

            $pageIndex++;

            if ($rowIndex > 0) {
                echo "</tbody></table></div>";
                echo "<div class='page page-break'>";
            } else {
                echo "<div class='page'>";
            }
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
                    <td class="label left">Company Name:</td>
                    <td class="value left"><?= htmlspecialchars($soa['company_name']) ?></td>

                    <td class="label right">SOA No:</td>
                    <td class="value right"><?= htmlspecialchars($soa['soa_no']) ?></td>
                </tr>
                <tr>
                    <td class="label left">Project Site:</td>
                    <td class="value left"><?= htmlspecialchars($soa['site_name']) ?></td>

                    <td class="label right">PO Number:</td>
                    <td class="value right">*</td>
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

        /* =========================
           ROW / NO DR ROW
        ========================= */
        if ($totalRows === 0) {
            ?>
            <tr>
                <td colspan="7" style="text-align:center;padding:14px;font-style:italic;">
                    No deliveries recorded for this SOA
                </td>
            </tr>
            <?php
        } else {
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
        }

        $rowIndex++;
    }

    /* =========================
       CLOSE LAST PAGE
    ========================= */
    echo "</tbody></table>";

    /* =========================
       TOTALS + TERMS
    ========================= */
    ?>
    <div class="totals-section">
        <div><strong>Total Quantity:</strong> <?= number_format($totalQty, 2) ?></div>
        <div><strong>Total Amount:</strong> ₱<?= number_format($totalAmt, 2) ?></div>
        <div><strong>Total DR Count:</strong> <?= $totalRows ?></div>

        <div class="terms-block">
            <strong>Terms of Payment:</strong><br>
            <?php if (($soa['terms'] ?? '') === '*'): ?>
                Cash payment is not accepted.
                Make all check payables to <strong>ALPHASAND AGGREGATES TRADING</strong>
            <?php else: ?>
                <strong><?= htmlspecialchars($soa['terms']) ?></strong>
                Days upon presentation of SOA.
                Make all check payables to <strong>ALPHASAND AGGREGATES TRADING</strong>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
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
