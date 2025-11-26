<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/admin.php'); exit;}
$id=intval($_POST['admin_id']); $old=$conn->prepare("SELECT * FROM admin WHERE admin_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin);
// if admin table has is_deleted column:
try {
  $s=$conn->prepare("UPDATE admin SET status='disabled', date_edited=:date_edited, edited_by=:edited_by WHERE admin_id=:id");
  $s->execute([':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id]);
  audit_log('admin',$id,'DELETE',$oldData,['status'=>'disabled'],$admin);
  $_SESSION['alert']=['type'=>'success','message'=>'Admin disabled'];
} catch(Exception $e){
  $_SESSION['alert']=['type'=>'danger','message'=>'Error: '.$e->getMessage()];
}
header('Location: ../../pages/admin.php'); exit;
