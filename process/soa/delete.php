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

$soa_id = (int)($_POST['soa_id'] ?? 0);
if ($soa_id <= 0) json_out(false,'Invalid SOA ID');

$old = $conn->prepare("SELECT * FROM statement_of_account WHERE soa_id=:id AND is_deleted=0");
$old->execute([':id'=>$soa_id]);
$oldData = $old->fetch(PDO::FETCH_ASSOC);
if (!$oldData) json_out(false,'SOA not found');

$cnt = $conn->prepare("SELECT COUNT(*) FROM delivery WHERE soa_id=:id AND is_deleted=0");
$cnt->execute([':id'=>$soa_id]);
if ((int)$cnt->fetchColumn() > 0) {
  json_out(false,'Cannot delete SOA with deliveries');
}

$audit = audit_on_update($admin);

$del = $conn->prepare("
  UPDATE statement_of_account
  SET is_deleted=1, date_edited=:de, edited_by=:eb
  WHERE soa_id=:id
");
$del->execute([
  ':de'=>$audit['date_edited'],
  ':eb'=>$audit['edited_by'],
  ':id'=>$soa_id
]);

audit_log('statement_of_account',$soa_id,'DELETE',$oldData,['is_deleted'=>1],$admin);
json_out(true,'SOA deleted');
