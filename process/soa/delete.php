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

$soa_id = (int)($_POST['soa_id'] ?? 0);
if ($soa_id <= 0) json_out(false, 'Invalid SOA ID');

try {
  $oldStmt = $conn->prepare("SELECT * FROM statement_of_account WHERE soa_id = :id AND is_deleted = 0");
  $oldStmt->execute([':id' => $soa_id]);
  $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
  if (!$old) json_out(false, 'SOA not found');

  if (($old['status'] ?? '') === 'finalized') {
    json_out(false, 'Finalized SOA cannot be deleted');
  }

  // prevent delete if has deliveries
  $cnt = $conn->prepare("SELECT COUNT(*) FROM delivery WHERE soa_id = :id AND is_deleted = 0");
  $cnt->execute([':id' => $soa_id]);
  $has = (int)$cnt->fetchColumn();
  if ($has > 0) {
    json_out(false, 'Cannot delete SOA with deliveries. Remove deliveries first.');
  }

  $audit = audit_on_update($admin);

  $del = $conn->prepare("
    UPDATE statement_of_account
    SET is_deleted = 1,
        date_edited = :date_edited,
        edited_by   = :edited_by
    WHERE soa_id = :id
  ");
  $del->execute([
    ':date_edited' => $audit['date_edited'],
    ':edited_by'   => $audit['edited_by'],
    ':id'          => $soa_id
  ]);

  audit_log('statement_of_account', $soa_id, 'DELETE', $old, ['is_deleted' => 1], $admin);

  json_out(true, 'SOA deleted');

} catch (Throwable $e) {
  http_response_code(500);
  json_out(false, 'Error: ' . $e->getMessage());
}
