<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){$_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/contractor.php'); exit;}
$id=intval($_POST['contractor_id']); $old=$conn->prepare("SELECT * FROM contractor WHERE contractor_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin);
$s=$conn->prepare("UPDATE contractor SET contractor_name=:contractor_name,contact_person=:contact_person,contact_no=:contact_no,email=:email,status=:status,date_edited=:date_edited,edited_by=:edited_by WHERE contractor_id=:id");
$s->execute([':contractor_name'=>$_POST['contractor_name'],':contact_person'=>$_POST['contact_person'],':contact_no'=>$_POST['contact_no'],':email'=>$_POST['email'],':status'=>$_POST['status']??'active',':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id]);
audit_log('contractor',$id,'UPDATE',$oldData,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Contractor updated']; header('Location: ../../pages/contractor.php'); exit;
