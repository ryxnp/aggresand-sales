<?php
session_start();
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../helpers/audit_fields.php';
require_once __DIR__.'/../../helpers/audit.php';
$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) { $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/contractor.php'); exit; }
$a = audit_on_create($admin);
$s = $conn->prepare("INSERT INTO contractor (contractor_name,contact_person,contact_no,email,status,is_deleted,date_created,date_edited,created_by,edited_by) VALUES (:contractor_name,:contact_person,:contact_no,:email,:status,0,:date_created,:date_edited,:created_by,:edited_by)");
$s->execute([':contractor_name'=>$_POST['contractor_name'],':contact_person'=>$_POST['contact_person'],':contact_no'=>$_POST['contact_no'],':email'=>$_POST['email'],':status'=>$_POST['status']??'active',':date_created'=>$a['date_created'],':date_edited'=>$a['date_edited'],':created_by'=>$a['created_by'],':edited_by'=>$a['edited_by']]);
$id=$conn->lastInsertId(); audit_log('contractor',$id,'CREATE',null,$_POST,$admin);
$_SESSION['alert']=['type'=>'success','message'=>'Contractor created']; header('Location: ../../pages/contractor.php'); exit;
