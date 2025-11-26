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
  $stmt = $conn->prepare("UPDATE company SET company_name=:company_name,address=:address,contact_no=:contact_no,email=:email,status=:status,date_edited=:date_edited,edited_by=:edited_by WHERE company_id=:id");
  $stmt->execute([
    ':company_name'=>$_POST['company_name'],
    ':address'=>$_POST['address'],
    ':contact_no'=>$_POST['contact_no'],
    ':email'=>$_POST['email'],
    ':status'=>$_POST['status'] ?? 'active',
    ':date_edited'=>$audit['date_edited'],
    ':edited_by'=>$audit['edited_by'],
    ':id'=>$id
  ]);
  audit_log('company',$id,'UPDATE',$oldData,$_POST,$admin);
  $_SESSION['alert']=['type'=>'success','message'=>'Company updated'];
}
header('Location: ../../pages/company.php');
exit;
