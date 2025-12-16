<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/audit_fields.php';
require_once __DIR__ . '/../../helpers/audit.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($ok, $message, $extra = []) {
  echo json_encode(array_merge([
    'ok' => (bool)$ok,
    'message' => $message
  ], $extra));
  exit;
}

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) {
  http_response_code(401);
  json_out(false, 'Not authenticated');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  json_out(false, 'Method not allowed');
}

$company_id = (int)($_POST['company_id'] ?? 0);
$site_id    = (int)($_POST['site_id'] ?? 0);
$soa_date   = trim($_POST['soa_date'] ?? '');
$terms_raw  = trim($_POST['terms'] ?? '');
$remarks    = trim($_POST['remarks'] ?? '');

$terms = ($terms_raw !== '' ? (int)$terms_raw : null);

if ($company_id <= 0) json_out(false, 'Company is required');
if ($site_id <= 0)    json_out(false, 'Site is required');
if ($soa_date === '') json_out(false, 'SOA date is required');
if ($terms === null || $terms <= 0) json_out(false, 'Terms (days) is required');

try {
  $conn->beginTransaction();

  // --- Generate SOA number: SOA-YYYY-00001 (per year)
  $year = (int)date('Y', strtotime($soa_date));
  $prefix = "SOA-$year-";

  // Find latest SOA of this year
  $stmt = $conn->prepare("
    SELECT soa_no
    FROM statement_of_account
    WHERE soa_no LIKE :prefix
    ORDER BY soa_no DESC
    LIMIT 1
  ");
  $stmt->execute([':prefix' => $prefix . '%']);
  $last = $stmt->fetchColumn();

  $nextSeq = 1;
  if ($last) {
    // last format: SOA-YYYY-00012
    $parts = explode('-', $last);
    $seq = (int)end($parts);
    if ($seq > 0) $nextSeq = $seq + 1;
  }

  $soa_no = $prefix . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);

  // --- Insert
  $audit = audit_on_create($admin);

  $ins = $conn->prepare("
    INSERT INTO statement_of_account
      (soa_no, company_id, site_id, soa_date, terms, remarks,
       status, is_deleted,
       date_created, date_edited, created_by, edited_by)
    VALUES
      (:soa_no, :company_id, :site_id, :soa_date, :terms, :remarks,
       'draft', 0,
       :date_created, :date_edited, :created_by, :edited_by)
  ");

  $ins->execute([
    ':soa_no'       => $soa_no,
    ':company_id'   => $company_id,
    ':site_id'      => $site_id,
    ':soa_date'     => $soa_date,
    ':terms'        => $terms,
    ':remarks'      => ($remarks !== '' ? $remarks : null),
    ':date_created' => $audit['date_created'],
    ':date_edited'  => $audit['date_edited'],
    ':created_by'   => $audit['created_by'],
    ':edited_by'    => $audit['edited_by'],
  ]);

  $soa_id = (int)$conn->lastInsertId();

  audit_log('statement_of_account', $soa_id, 'CREATE', null, [
    'soa_no' => $soa_no,
    'company_id' => $company_id,
    'site_id' => $site_id,
    'soa_date' => $soa_date,
    'terms' => $terms,
    'remarks' => $remarks,
    'status' => 'draft',
  ], $admin);

  $conn->commit();

  json_out(true, 'SOA created', [
    'soa' => [
      'soa_id' => $soa_id,
      'soa_no' => $soa_no,
      'company_id' => $company_id,
      'site_id' => $site_id,
      'soa_date' => $soa_date,
      'terms' => $terms,
      'status' => 'draft',
    ]
  ]);

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  json_out(false, 'Error: ' . $e->getMessage());
}
