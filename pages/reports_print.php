<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* ---------------------------------------------------------
   VALIDATE INPUT
--------------------------------------------------------- */
$soa_id = (int)($_GET['soa_id'] ?? 0);
if ($soa_id <= 0) {
    die('Invalid SOA');
}

/* ---------------------------------------------------------
   FETCH SOA HEADER
--------------------------------------------------------- */
$soaStmt = $conn->prepare("
    SELECT s.soa_no, s.terms, s.status,
           co.company_name,
           si.site_name
    FROM statement_of_account s
    JOIN company co ON s.company_id = co.company_id
    JOIN site si ON s.site_id = si.site_id
    WHERE s.soa_id = :id AND s.is_deleted = 0
    LIMIT 1
");
$soaStmt->execute([':id' => $soa_id]);
$soa = $soaStmt->fetch(PDO::FETCH_ASSOC);

if (!$soa) {
    die('SOA not found');
}

/* Optional hard rule: only print finalized */
if ($soa['status'] !== 'finalized') {
    die('SOA is not finalized');
}

/* ---------------------------------------------------------
   FETCH DELIVERIES UNDER THIS SOA
--------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        d.delivery_date,
        d.dr_no,
        d.material,
        d.quantity,
        d.unit_price,
        d.po_number,
        d.terms,
        c.customer_name,
        t.plate_no
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE d.soa_id = :soa_id
      AND d.is_deleted = 0
    ORDER BY d.delivery_date ASC, d.dr_no ASC
");
$stmt->execute([':soa_id' => $soa_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   COMPUTE TOTALS
--------------------------------------------------------- */
$total_qty = 0;
$total_amount = 0;
$po_list = [];

foreach ($rows as $r) {
    $total_qty += (float)$r['quantity'];
    $total_amount += (float)$r['quantity'] * (float)$r['unit_price'];

    if (!empty($r['po_number'])) {
        $po_list[$r['po_number']] = true;
    }
}
$po_numbers_display = implode(", ", array_keys($po_list));

/* ---------------------------------------------------------
   HEADER VALUES
--------------------------------------------------------- */
$soa_number   = $soa['soa_no'];
$company_name = $soa['company_name'];
$site_name    = $soa['site_name'];
$terms_display = $soa['terms'];
$header_image = "../assets/header.png";

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Statement of Account <?= htmlspecialchars($soa_number) ?></title>  
    <style>
        @import url('../css/print.css');
    </style>  
</head>

<body>
    <div class="print-date">
        <?= date('d–F–Y') ?>
    </div>
    <?php
    $rowsPerPage = 22;
    $count = 0;
    $page = 0;

    function print_header_block($header_image, $soa_number, $company_name, $site_name, $po_numbers_display, $terms_display, $page)
    {
    ?>
        <img src="<?= $header_image ?>" class="header-image">

        <table class="soa-header-table">
            <tr>
                <td><strong>Company Name:</strong> <?= htmlspecialchars($company_name) ?></td>
                <td><strong>Statement of Account No:</strong> <?= htmlspecialchars($soa_number) ?></td>
            </tr>
            <tr>
                <td><strong>Project Site:</strong> <?= htmlspecialchars($site_name) ?></td>
                <td><strong>PO Number:</strong> *</td>

            </tr>
            <tr>
                <td><strong>Billing Date:</strong> <?= date("m/d/Y") ?></td>
            </tr>
        </table>
    <?php
    }

    function print_table_header()
    {
        echo '
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
    <tbody>';
    }

    foreach ($rows as $row) {

        if ($count % $rowsPerPage === 0) {
            $page++;
            if ($count > 0) {
                echo "</tbody></table></div><div class='page page-break'>";
            } else {
                echo "<div class='page'>";
            }

            print_header_block(
                $header_image,
                $soa_number,
                $company_name,
                $site_name,
                $po_numbers_display,
                $terms_display,
                $page
            );

            print_table_header();
        }

        $amount = (float)$row['quantity'] * (float)$row['unit_price'];

        echo "<tr>
        <td>{$row['delivery_date']}</td>
        <td>{$row['dr_no']}</td>
        <td>{$row['plate_no']}</td>
        <td>{$row['material']}</td>
        <td style='text-align:right'>" . number_format($row['quantity'], 2) . "</td>
        <td style='text-align:right'>" . number_format($row['unit_price'], 2) . "</td>
        <td style='text-align:right'>" . number_format($amount, 2) . "</td>
    </tr>";

        $count++;
    }

    if ($count === 0) {
        echo "<div class='page'>";
        print_header_block($header_image, $soa_number, $company_name, $site_name, '', $terms_display, 1);
        echo "<p>No records found.</p></div>";
    } else {
        echo "</tbody></table>";

        echo "
    <div class='totals-section'>
        <div><strong>Total Quantity:</strong> " . number_format($total_qty, 2) . "</div>
        <div><strong>Total Amount:</strong> ₱" . number_format($total_amount, 2) . "</div>
        <div><strong>Total DR Count:</strong> " . count($rows) . "</div>

        <div class='terms-block'>
            <strong>Terms of Payment:</strong><br>
            <?php if ($terms_display === '*'): ?>
                <span class='terms-highlight'>
                    NO CASH PAYMENT WILL BE ACCEPTED
                </span>
            <?php else: ?>
                <?= htmlspecialchars($terms_display) ?> 
            <?php endif; ?>
        </div>
</div>
    </div>";

        echo "
    <div class='footer'>
        <table class='footer-table'>
            <tr>
                <td>
                    <strong>Prepared By:</strong><br><br><br>
                    <span class='sig-name'>{$_SESSION['username']}</span><br>
                    <span class='sig-role'>Sales and Operation Officer</span>
                </td>
                <td>
                    <strong>Checked By:</strong><br><br><br>
                    <span class='sig-name'>Ma. Christa Agustin</span><br>
                    <span class='sig-role'>Accounting & Admin Officer</span>
                </td>
                <td>
                    <strong>Approved By:</strong><br><br><br>
                    <span class='sig-name'>Analyn Buenviaje</span><br>
                    <span class='sig-role'>General Manager</span>
                </td>
                <td>
                    <strong>Received By:</strong><br><br><br>
                    <span class='sig-name'>Customer's Name</span><br>
                    <span class='sig-role'>&nbsp;</span>
                </td>
            </tr>
        </table>
    </div>
    </div>";
    }
    ?>

    <script>
        window.print();
    </script>
</body>

</html>