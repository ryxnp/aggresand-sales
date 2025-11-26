<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// same filters as reports_print.php
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

$sql = "
    SELECT
        d.delivery_date,
        d.billing_date,
        d.dr_no,
        d.material,
        d.quantity,
        d.unit_price,
        (d.quantity * d.unit_price) AS amount,
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

// CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="deliveries_export_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');

// header row
fputcsv($out, [
    'Delivery Date', 'Billing Date', 'DR No.', 'Customer', 'Company', 'Site',
    'Plate No.', 'Material', 'Quantity', 'Unit Price', 'Amount', 'Status'
]);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['delivery_date'],
        $r['billing_date'],
        $r['dr_no'],
        $r['customer_name'],
        $r['company_name'],
        $r['site_name'],
        $r['plate_no'],
        $r['material'],
        number_format((float)$r['quantity'], 3, '.', ''),
        number_format((float)$r['unit_price'], 2, '.', ''),
        number_format((float)$r['amount'], 2, '.', ''),
        $r['status'],
    ]);
}

fclose($out);
exit;
