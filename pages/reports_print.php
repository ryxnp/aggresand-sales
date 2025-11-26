<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// reuse same filters as reports.php
$customer_id   = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$company_id    = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$site_id       = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$material_id   = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$status_filter = $_GET['status_filter'] ?? '';
$delivery_from = $_GET['delivery_from'] ?? '';
$delivery_to   = $_GET['delivery_to'] ?? '';
$billing_from  = $_GET['billing_from'] ?? '';
$billing_to    = $_GET['billing_to'] ?? '';
$search_text   = trim($_GET['q'] ?? '');

$where  = "d.is_deleted = 0";
$params = [];

// same where-building as reports.php (simplified – no contractor here)
if ($customer_id > 0) {
    $where .= " AND c.customer_id = :customer_id";
    $params[':customer_id'] = $customer_id;
}
if ($company_id > 0) {
    $where .= " AND c.company_id = :company_id";
    $params[':company_id'] = $company_id;
}
if ($site_id > 0) {
    $where .= " AND c.site_id = :site_id";
    $params[':site_id'] = $site_id;
}
if ($material_id > 0) {
    $mStmt = $conn->prepare("SELECT material_name FROM materials WHERE material_id = :mid");
    $mStmt->execute([':mid' => $material_id]);
    $mRow = $mStmt->fetch(PDO::FETCH_ASSOC);
    if ($mRow) {
        $where .= " AND d.material = :material_name";
        $params[':material_name'] = $mRow['material_name'];
    }
}
if ($status_filter !== '') {
    $where .= " AND d.status = :status";
    $params[':status'] = $status_filter;
}
if ($delivery_from !== '') {
    $where .= " AND d.delivery_date >= :delivery_from";
    $params[':delivery_from'] = $delivery_from;
}
if ($delivery_to !== '') {
    $where .= " AND d.delivery_date <= :delivery_to";
    $params[':delivery_to'] = $delivery_to;
}
if ($billing_from !== '') {
    $where .= " AND d.billing_date >= :billing_from";
    $params[':billing_from'] = $billing_from;
}
if ($billing_to !== '') {
    $where .= " AND d.billing_date <= :billing_to";
    $params[':billing_to'] = $billing_to;
}
if ($search_text !== '') {
    $where .= " AND (
        d.dr_no LIKE :q
        OR d.material LIKE :q
        OR c.customer_name LIKE :q
        OR co.company_name LIKE :q
        OR s.site_name LIKE :q
        OR t.plate_no LIKE :q
    )";
    $params[':q'] = '%' . $search_text . '%';
}

// fetch all matching rows (no pagination)
$sql = "
    SELECT
        d.delivery_date,
        d.billing_date,
        d.dr_no,
        d.material,
        d.quantity,
        d.unit_price,
        d.status,
        c.customer_name,
        co.company_name,
        s.site_name,
        t.plate_no
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN company co ON c.company_id = co.company_id
    LEFT JOIN site s ON c.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
    ORDER BY d.delivery_date ASC, d.dr_no ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// totals
$total_qty = 0;
$total_amount = 0;
$total_dr = [];

foreach ($rows as $r) {
    $total_qty    += (float)$r['quantity'];
    $total_amount += (float)$r['quantity'] * (float)$r['unit_price'];
    if ($r['dr_no'] !== '') {
        $total_dr[$r['dr_no']] = true;
    }
}
$total_dr_count = count($total_dr);

// customer name for header
$customer_name = 'All Customers';
if ($customer_id > 0) {
    $cu = $conn->prepare("SELECT customer_name FROM customer WHERE customer_id = :cid");
    $cu->execute([':cid' => $customer_id]);
    $rowCu = $cu->fetch(PDO::FETCH_ASSOC);
    if ($rowCu) {
        $customer_name = $rowCu['customer_name'];
    }
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Statement of Account</title>
<style>
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        font-size: 11px;
    }
    .print-page {
        width: 190mm;
        min-height: 277mm;
        margin: 0 auto;
        padding: 5mm 10mm;
        box-sizing: border-box;
    }
    .page-break {
        page-break-before: always;
    }
    h1, h2, h3, h4, h5 {
        margin: 0;
        padding: 0;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    .company-info {
        font-size: 10px;
    }
    .statement-title {
        text-align: right;
        font-size: 12px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }
    th, td {
        border: 1px solid #000;
        padding: 3px 4px;
    }
    th {
        background: #f0f0f0;
        text-align: center;
    }
    td.text-right {
        text-align: right;
    }
    td.text-center {
        text-align: center;
    }
    td.text-left {
        text-align: left;
    }
    .totals-row td {
        font-weight: bold;
    }
    .footer-signatures {
        margin-top: 20px;
        font-size: 10px;
    }
    .footer-signatures td {
        border: none;
        padding: 10px 4px 0;
    }
</style>
</head>
<body>

<?php
$rowsPerPage = 25; // adjust until it perfectly fits one A4 page visually
$count = 0;
$page = 0;

function print_table_header() {
    echo '<table>';
    echo '<thead><tr>
        <th>Date</th>
        <th>DR No.</th>
        <th>Plate No.</th>
        <th>Material</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Amount</th>
        <th>Remarks</th>
    </tr></thead><tbody>';
}

foreach ($rows as $row) {
    if ($count % $rowsPerPage === 0) {
        if ($count > 0) {
            // close previous page
            echo '</tbody></table>';
            echo '</div>'; // .print-page
            echo '<div class="print-page page-break">';
        } else {
            echo '<div class="print-page">';
        }

        $page++;

        // HEADER PER PAGE
        ?>
        <div class="header">
            <div class="company-info">
                <strong>AGGRESAND Quarrying Inc.</strong><br>
                <!-- You can replace this block with real address / contact -->
                Brgy. Manuali, Bacolor, Pampanga<br>
                Tel. No.: 0000-000-0000
            </div>
            <div class="statement-title">
                <strong>STATEMENT OF ACCOUNT</strong><br>
                <small>Date: <?= date('d-M-Y') ?></small><br>
                <small>Page: <?= $page ?></small>
            </div>
        </div>

        <div style="margin-bottom:8px;font-size:10px;">
            <strong>Customer:</strong> <?= htmlspecialchars($customer_name) ?><br>
            <?php if ($billing_from || $billing_to): ?>
                <strong>Billing Date:</strong>
                <?= $billing_from ?: 'Any' ?> to <?= $billing_to ?: 'Any' ?><br>
            <?php endif; ?>
        </div>
        <?php

        print_table_header();
    }

    $amount = (float)$row['quantity'] * (float)$row['unit_price'];

    echo '<tr>';
    echo '<td class="text-center">' . htmlspecialchars($row['delivery_date']) . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($row['dr_no']) . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($row['plate_no']) . '</td>';
    echo '<td class="text-left">'   . htmlspecialchars($row['material']) . '</td>';
    echo '<td class="text-right">'  . number_format((float)$row['quantity'], 3) . '</td>';
    echo '<td class="text-right">'  . number_format((float)$row['unit_price'], 2) . '</td>';
    echo '<td class="text-right">'  . number_format($amount, 2) . '</td>';
    echo '<td class="text-left"></td>';
    echo '</tr>';

    $count++;
}

if ($count === 0) {
    // still need one page
    echo '<div class="print-page">';
    ?>
    <div class="header">
        <div class="company-info">
            <strong>AGGRESAND Quarrying Inc.</strong><br>
        </div>
        <div class="statement-title">
            <strong>STATEMENT OF ACCOUNT</strong><br>
            <small>Date: <?= date('d-M-Y') ?></small>
        </div>
    </div>
    <p>No records found.</p>
    </div>
    <?php
} else {
    // close last table + page
    echo '</tbody></table>';

    // TOTALS ON LAST PAGE
    ?>
    <table style="margin-top:10px;">
        <tr class="totals-row">
            <td style="border:none;" colspan="3"><strong>Sub-Total / Grand Total:</strong></td>
            <td style="border:none;" class="text-right">Qty</td>
            <td class="text-right"><?= number_format($total_qty, 3) ?></td>
            <td class="text-right">Amount</td>
            <td class="text-right"><?= number_format($total_amount, 2) ?></td>
            <td style="border:none;"></td>
        </tr>
        <tr>
            <td style="border:none;" colspan="3"><strong>Total DR No.:</strong></td>
            <td style="border:none;" colspan="5"><?= (int)$total_dr_count ?></td>
        </tr>
    </table>

    <table class="footer-signatures">
        <tr>
            <td><strong>Prepared By:</strong><br><br>__________________________<br><strong>Kristine Hanna Argao</strong><br><small>Sales &amp; Purchasing Officer</small></td>
            <td><strong>Checked By:</strong><br><br>__________________________<br><strong>Ma. Thricia Agustin</strong><br><small>?</small></td>
            <td><strong>Approved By:</strong><br><br>__________________________<br><strong>Analyn Buenviaje</strong><br><small>General Manager</small></td></td>
            <td><strong>Date Submitted:</strong><br><br>__________________________<br><strong>Recieved By:</strong><br><small>(Signature over Printed Name)</small></td>
        </tr>
    </table>

    </div><!-- .print-page -->
    <?php
}
?>

</body>
</html>
