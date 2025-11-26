<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){$_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/site.php'); exit;}
$a=audit_on_create($admin);
$s=$conn->prepare("INSERT INTO site (site_name,remarks,location,status,is_deleted,date_created,date_edited,created_by,edited_by) VALUES (:site_name,:remarks,:location,:status,0,:date_created,:date_edited,:created_by,:edited_by)");
$s->execute([':site_name'=>$_POST['site_name'],':remarks'=>$_POST['remarks'],':location'=>$_POST['location'],':status'=>$_POST['status']??'active',':date_created'=>$a['date_created'],':date_edited'=>$a['date_edited'],':created_by'=>$a['created_by'],':edited_by'=>$a['edited_by']]);
$id=$conn->lastInsertId(); audit_log('site',$id,'CREATE',null,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Site created']; header('Location: ../../pages/site.php'); exit;
