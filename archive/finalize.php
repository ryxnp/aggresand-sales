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

$soa_id = (int)($_POST['soa_id'] ?? 0);
if ($soa_id <= 0) json_out(false, 'Invalid SOA ID');

try {
  $conn->beginTransaction();

  $oldStmt = $conn->prepare("
    SELECT *
    FROM statement_of_account
    WHERE soa_id = :id AND is_deleted = 0
    FOR UPDATE
  ");
  $oldStmt->execute([':id' => $soa_id]);
  $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

  if (!$old) {
    $conn->rollBack();
    json_out(false, 'SOA not found');
  }

  if (($old['status'] ?? '') === 'finalized') {
    $conn->rollBack();
    json_out(false, 'SOA is already finalized', ['status' => 'finalized']);
  }

  $audit = audit_on_update($admin);

  // finalized_at column may not exist; only set if it exists
  $hasFinalizedAt = false;
  try {
    $chkCol = $conn->query("SHOW COLUMNS FROM statement_of_account LIKE 'finalized_at'")->fetch();
    $hasFinalizedAt = (bool)$chkCol;
  } catch (Throwable $e) {
    $hasFinalizedAt = false;
  }

  $sql = "
    UPDATE statement_of_account
    SET status = 'finalized',
        date_edited = :date_edited,
        edited_by   = :edited_by
  ";
  if ($hasFinalizedAt) {
    $sql .= ", finalized_at = NOW() ";
  }
  $sql .= " WHERE soa_id = :id AND is_deleted = 0";

$upd = $conn->prepare("
    UPDATE statement_of_account
    SET status = 'finalized',
        billing_date = CURDATE(),
        date_edited = :date_edited,
        edited_by = :edited_by
    WHERE soa_id = :id
");
  $upd->execute([
    ':date_edited' => $audit['date_edited'],
    ':edited_by'   => $audit['edited_by'],
    ':id'          => $soa_id
  ]);

  audit_log('statement_of_account', $soa_id, 'FINALIZE', $old, ['status' => 'finalized'], $admin);

  $conn->commit();

  json_out(true, 'SOA finalized', [
    'soa_id' => $soa_id,
    'status' => 'finalized'
  ]);

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500);
  json_out(false, 'Error: ' . $e->getMessage());
}
