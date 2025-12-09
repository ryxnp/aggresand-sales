<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* ---------------------------------------------------------
   FETCH FILTERS
--------------------------------------------------------- */
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

/* ---------------------------------------------------------
   BUILD WHERE CLAUSE
--------------------------------------------------------- */
$where  = "d.is_deleted = 0";
$params = [];

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
    if ($mRow = $mStmt->fetch(PDO::FETCH_ASSOC)) {
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

/* ---------------------------------------------------------
   FETCH ALL MATCHING RECORDS
--------------------------------------------------------- */
$sql = "
    SELECT
        d.delivery_date,
        d.billing_date,
        d.dr_no,
        d.material,
        d.quantity,
        d.unit_price,
        d.po_number,
        d.terms,
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
   FETCH CUSTOMER / COMPANY / SITE INFO
--------------------------------------------------------- */
$customer_name = "";
$company_name = "";
$site_name = "";
$terms_display = "";

if ($customer_id > 0) {
    $cu = $conn->prepare("
        SELECT c.customer_name, co.company_name, s.site_name
        FROM customer c
        LEFT JOIN company co ON c.company_id = co.company_id
        LEFT JOIN site s ON c.site_id = s.site_id
        WHERE c.customer_id = :cid
    ");
    $cu->execute([':cid' => $customer_id]);
    if ($info = $cu->fetch(PDO::FETCH_ASSOC)) {
        $customer_name = $info['company_name']; // You requested Company Name replaces "Customer"
        $company_name = $info['customer_name'];
        $site_name = $info['site_name'];
    }
}

/* ---------------------------------------------------------
   SOA NUMBER GENERATION
--------------------------------------------------------- */
$soa_number = "SOA-" . date("Ymd") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);

/* ---------------------------------------------------------
   HTML OUTPUT
--------------------------------------------------------- */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Statement of Account</title>

<style>
    @page { size: A4 portrait; margin: 10mm; }
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; }

    .page { width: 190mm; min-height: 277mm; margin: 0 auto; padding: 5mm 5mm 10mm 5mm; box-sizing: border-box; position: relative; }
    .page-break { page-break-before: always; }

    .header-image { width: 100%; }

    .header-info { margin-top: 5px; font-size: 11px; }
    .header-info div { margin-bottom: 2px; }

    .page-number { text-align: right; font-size: 11px; margin-bottom: 5px; }

    table.soa-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.soa-table th, table.soa-table td {
        border: 1px solid #000; padding: 4px; font-size: 10px;
    }
    table.soa-table th { background: #f0f0f0; text-align: center; }

    .totals-section { margin-top: 15px; font-size: 11px; }
    .totals-section div { margin-bottom: 3px; }

    .footer { position: absolute; bottom: 10mm; left: 0; width: 100%; }
</style>
</head>
<body>

<?php
$rowsPerPage = 22;
$count = 0;
$page = 0;

function print_header_block($header_image, $soa_number, $customer_name, $company_name, $site_name, $billing_from, $billing_to, $po_numbers_display, $terms_display, $page) {
    ?>

    <img src="<?= $header_image ?>" class="header-image">

    <div class="page-number">Statement of Account | Page <?= $page ?></div>

    <div class="header-info">
        <!-- SOA DETAILS TABLE -->
<table style="width:100%; font-size:11px; border-collapse:collapse; margin-top:5px;">
    <tr>
        <td>
            <strong>Company Name:</strong> <?= htmlspecialchars($customer_name) ?>
        </td>
        <td>
            <strong>Statement of Account No:</strong> <?= htmlspecialchars($soa_number) ?>
        </td>
        <td>
            <strong>Billing Date:</strong> <?= htmlspecialchars($billing_from ?: date("m/d/Y")) ?>
        </td>
    </tr>

    <tr>
        <td>
            <strong>Attention To:</strong> •
        </td>
        <td>
            <strong>PO Number:</strong> <?= htmlspecialchars($po_numbers_display) ?>
        </td>
        <td>
            <strong> </strong>
        </td>
    </tr>

    <tr>
        <td>
            <strong>Project Site:</strong> <?= htmlspecialchars($site_name) ?>
        </td>
        <td>
            <strong>Date:</strong> <?= date("m/d/Y") ?>
        </td>
        <td>
            <strong> </strong>
        </td>
    </tr>
</table>

    </div>

    <?php
}

function print_table_header() {
    echo '
    <table class="soa-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>DR No.</th>
            <th>Plate No.</th>
            <th>Material</th>
            <th>Qty</th>
            <th>U.Price</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>';
}

$header_image = "../assets/header.png";

// Extract terms from ANY delivery (latest non-empty)
foreach ($rows as $r) {
    if (!empty($r['terms'])) {
        $terms_display = $r['terms'];
        break;
    }
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
            $customer_name,
            $company_name,
            $site_name,
            $billing_from,
            $billing_to,
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
    print_header_block($header_image, $soa_number, $customer_name, $company_name, $site_name, $billing_from, $billing_to, $po_numbers_display, $terms_display, 1);
    echo "<p>No records found.</p></div>";
} else {
    echo "</tbody></table>";

    echo "
    <div class='totals-section'>
        <div><strong>Total Quantity:</strong> " . number_format($total_qty, 2) . "</div>
        <div><strong>Total Amount:</strong> ₱" . number_format($total_amount, 2) . "</div>
        <div><strong>Total DR Count:</strong> " . count($po_list) . "</div>
    </div>";

    echo "
    <div class='footer'>
        <table style='width:100%; font-size:11px; border:none;'>
            <tr>
                <td><strong>Prepared By:</strong><br><br>__________________________<br>{$_SESSION['username']}</td>
                <td><strong>Checked By:</strong><br><br>__________________________</td>
                <td><strong>Approved By:</strong><br><br>__________________________</td>
                <td><strong>Date Submitted:</strong><br><br>__________________________</td>
            </tr>
        </table>
    </div>

    </div>";
}
?>

</body>
</html>
