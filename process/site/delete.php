<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){$_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/site.php'); exit;}
$id=intval($_POST['site_id']); $old=$conn->prepare("SELECT * FROM site WHERE site_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin); $s=$conn->prepare("UPDATE site SET is_deleted=1,date_edited=:date_edited,edited_by=:edited_by WHERE site_id=:id"); $s->execute([':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id]);
audit_log('site',$id,'DELETE',$oldData,['is_deleted'=>1],$admin); $_SESSION['alert']=['type'=>'success','message'=>'Site deleted']; header('Location: ../../pages/site.php'); exit;
