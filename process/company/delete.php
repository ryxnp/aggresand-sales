<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/audit_fields.php';
require_once __DIR__ . '/../../helpers/audit.php';

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/company.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = intval($_POST['company_id']);
  $old = $conn->prepare("SELECT * FROM company WHERE company_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
  $audit = audit_on_update($admin);
  $stmt = $conn->prepare("UPDATE company SET is_deleted=1,date_edited=:date_edited,edited_by=:edited_by WHERE company_id=:id");
  $stmt->execute([':date_edited'=>$audit['date_edited'],':edited_by'=>$audit['edited_by'],':id'=>$id]);
  audit_log('company',$id,'DELETE',$oldData,['is_deleted'=>1],$admin);
  $_SESSION['alert']=['type'=>'success','message'=>'Company deleted'];
}
header('Location: ../../pages/company.php');
exit;
