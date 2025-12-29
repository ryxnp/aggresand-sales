<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/audit_fields.php';
require_once __DIR__ . '/../../helpers/audit.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($ok, $message, $extra = []) {
  echo json_encode(array_merge(['ok'=>(bool)$ok,'message'=>$message], $extra));
  exit;
}

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) { http_response_code(401); json_out(false,'Not authenticated'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); json_out(false,'Method not allowed');
}

$company_id   = (int)($_POST['company_id'] ?? 0);
$site_id      = (int)($_POST['site_id'] ?? 0);
$billing_date = trim($_POST['billing_date'] ?? '');
$terms        = (int)($_POST['terms'] ?? 0);
$remarks      = trim($_POST['remarks'] ?? '');

if ($company_id <= 0) json_out(false,'Company is required');
if ($site_id <= 0) json_out(false,'Site is required');
if ($billing_date === '') json_out(false,'Billing date is required');
if ($terms <= 0) json_out(false,'Terms is required');

try {
  $conn->beginTransaction();

  $year = (int)date('Y', strtotime($billing_date));
  $prefix = "SOA-$year-";

  $stmt = $conn->prepare("
    SELECT soa_no FROM statement_of_account
    WHERE soa_no LIKE :pfx
    ORDER BY soa_no DESC LIMIT 1
  ");
  $stmt->execute([':pfx'=>$prefix.'%']);
  $last = $stmt->fetchColumn();

  $seq = 1;
  if ($last) {
    $parts = explode('-', $last);
    $seq = ((int)end($parts)) + 1;
  }

  $soa_no = $prefix . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
  $audit  = audit_on_create($admin);

  $ins = $conn->prepare("
    INSERT INTO statement_of_account
      (soa_no, company_id, site_id, billing_date, terms, remarks,
       status, is_deleted,
       date_created, date_edited, created_by, edited_by)
    VALUES
      (:soa_no, :company_id, :site_id, :billing_date, :terms, :remarks,
       'draft', 0,
       :dc, :de, :cb, :eb)
  ");

  $ins->execute([
    ':soa_no'=>$soa_no,
    ':company_id'=>$company_id,
    ':site_id'=>$site_id,
    ':billing_date'=>$billing_date,
    ':terms'=>$terms,
    ':remarks'=>($remarks!==''?$remarks:null),
    ':dc'=>$audit['date_created'],
    ':de'=>$audit['date_edited'],
    ':cb'=>$audit['created_by'],
    ':eb'=>$audit['edited_by'],
  ]);

  $soa_id = (int)$conn->lastInsertId();
  audit_log('statement_of_account',$soa_id,'CREATE',null,$_POST,$admin);

  $conn->commit();
  json_out(true,'SOA created',['soa_id'=>$soa_id,'soa_no'=>$soa_no]);

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  json_out(false,'Error: '.$e->getMessage());
}
