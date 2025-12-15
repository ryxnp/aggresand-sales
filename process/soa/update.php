<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/audit_fields.php';
require_once __DIR__ . '/../../helpers/audit.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($ok, $message, $extra = []) {
  echo json_encode(array_merge(['ok' => (bool)$ok, 'message' => $message], $extra));
  exit;
}

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) { http_response_code(401); json_out(false, 'Not authenticated'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); json_out(false, 'Method not allowed'); }

$soa_id     = (int)($_POST['soa_id'] ?? 0);
$company_id = (int)($_POST['company_id'] ?? 0);
$site_id    = (int)($_POST['site_id'] ?? 0);
$soa_date   = trim($_POST['soa_date'] ?? '');
$terms      = (int)($_POST['terms'] ?? 0);
$remarks    = trim($_POST['remarks'] ?? '');

if ($soa_id <= 0) json_out(false, 'Invalid SOA ID');
if ($company_id <= 0) json_out(false, 'Company is required');
if ($site_id <= 0) json_out(false, 'Site is required');
if ($soa_date === '') json_out(false, 'SOA date is required');
if ($terms <= 0) json_out(false, 'Terms is required');

try {
  $oldStmt = $conn->prepare("SELECT * FROM statement_of_account WHERE soa_id = :id AND is_deleted = 0");
  $oldStmt->execute([':id' => $soa_id]);
  $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
  if (!$old) json_out(false, 'SOA not found');

  if (($old['status'] ?? '') === 'finalized') {
    json_out(false, 'SOA is finalized and cannot be edited');
  }

  $audit = audit_on_update($admin);

  $upd = $conn->prepare("
    UPDATE statement_of_account
    SET company_id = :company_id,
        site_id    = :site_id,
        soa_date   = :soa_date,
        terms      = :terms,
        remarks    = :remarks,
        date_edited= :date_edited,
        edited_by  = :edited_by
    WHERE soa_id = :id AND is_deleted = 0
  ");

  $upd->execute([
    ':company_id' => $company_id,
    ':site_id'    => $site_id,
    ':soa_date'   => $soa_date,
    ':terms'      => $terms,
    ':remarks'    => ($remarks !== '' ? $remarks : null),
    ':date_edited'=> $audit['date_edited'],
    ':edited_by'  => $audit['edited_by'],
    ':id'         => $soa_id
  ]);

  audit_log('statement_of_account', $soa_id, 'UPDATE', $old, $_POST, $admin);

  json_out(true, 'SOA updated');

} catch (Throwable $e) {
  http_response_code(500);
  json_out(false, 'Error: ' . $e->getMessage());
}
