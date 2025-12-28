<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/audit_fields.php';
require_once __DIR__ . '/../../helpers/audit.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($ok,$msg,$x=[]){
  echo json_encode(array_merge(['ok'=>(bool)$ok,'message'=>$msg],$x)); exit;
}

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) { http_response_code(401); json_out(false,'Not authenticated'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); json_out(false,'Method not allowed');
}

$soa_id       = (int)($_POST['soa_id'] ?? 0);
$company_id   = (int)($_POST['company_id'] ?? 0);
$site_id      = (int)($_POST['site_id'] ?? 0);
$billing_date = trim($_POST['billing_date'] ?? '');
$terms        = (int)($_POST['terms'] ?? 0);
$remarks      = trim($_POST['remarks'] ?? '');

if ($soa_id <= 0) json_out(false,'Invalid SOA ID');
if ($company_id <= 0) json_out(false,'Company required');
if ($site_id <= 0) json_out(false,'Site required');
if ($billing_date === '') json_out(false,'Billing date required');
if ($terms <= 0) json_out(false,'Terms required');

$old = $conn->prepare("SELECT * FROM statement_of_account WHERE soa_id=:id AND is_deleted=0");
$old->execute([':id'=>$soa_id]);
$oldData = $old->fetch(PDO::FETCH_ASSOC);
if (!$oldData) json_out(false,'SOA not found');

$audit = audit_on_update($admin);

$upd = $conn->prepare("
  UPDATE statement_of_account SET
    company_id=:company_id,
    site_id=:site_id,
    billing_date=:billing_date,
    terms=:terms,
    remarks=:remarks,
    date_edited=:de,
    edited_by=:eb
  WHERE soa_id=:id
");

$upd->execute([
  ':company_id'=>$company_id,
  ':site_id'=>$site_id,
  ':billing_date'=>$billing_date,
  ':terms'=>$terms,
  ':remarks'=>($remarks!==''?$remarks:null),
  ':de'=>$audit['date_edited'],
  ':eb'=>$audit['edited_by'],
  ':id'=>$soa_id
]);

audit_log('statement_of_account',$soa_id,'UPDATE',$oldData,$_POST,$admin);
json_out(true,'SOA updated');
