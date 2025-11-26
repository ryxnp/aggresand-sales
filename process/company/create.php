<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/audit_fields.php';
require_once __DIR__ . '/../../helpers/audit.php';

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) { $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/company.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $audit = audit_on_create($admin);
  $stmt = $conn->prepare("INSERT INTO company (company_name,address,contact_no,email,status,is_deleted,date_created,date_edited,created_by,edited_by) VALUES (:company_name,:address,:contact_no,:email,:status,0,:date_created,:date_edited,:created_by,:edited_by)");
  $stmt->execute([
    ':company_name'=>$_POST['company_name'],
    ':address'=>$_POST['address'],
    ':contact_no'=>$_POST['contact_no'],
    ':email'=>$_POST['email'],
    ':status'=>$_POST['status'] ?? 'active',
    ':date_created'=>$audit['date_created'],
    ':date_edited'=>$audit['date_edited'],
    ':created_by'=>$audit['created_by'],
    ':edited_by'=>$audit['edited_by']
  ]);
  $id = $conn->lastInsertId();
  audit_log('company',$id,'CREATE',null,$_POST,$admin);
  $_SESSION['alert']=['type'=>'success','message'=>'Company created'];
}
header('Location: ../../pages/company.php');
exit;
