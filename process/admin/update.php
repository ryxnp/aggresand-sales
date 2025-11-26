<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/admin.php'); exit;}
$id=intval($_POST['admin_id']); $old=$conn->prepare("SELECT * FROM admin WHERE admin_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin);
$updateStr = "UPDATE admin SET username=:username,email=:email,role=:role,status=:status,date_edited=:date_edited,edited_by=:edited_by";
$params=[':username'=>$_POST['username'],':email'=>$_POST['email'],':role'=>$_POST['role'],':status'=>$_POST['status']??'active',':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id];
if(!empty($_POST['password'])){ $hash=password_hash($_POST['password'],PASSWORD_DEFAULT); $updateStr.=", password=:password"; $params[':password']=$hash; }
$updateStr.=" WHERE admin_id=:id";
$s=$conn->prepare($updateStr); $s->execute($params);
audit_log('admin',$id,'UPDATE',$oldData,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Admin updated']; header('Location: ../../pages/admin.php'); exit;
