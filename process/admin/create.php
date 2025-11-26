<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/admin.php'); exit;}
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $a=audit_on_create($admin);
  $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $s=$conn->prepare("INSERT INTO admin (username,email,role,password,last_login,status,date_created,date_edited,created_by,edited_by) VALUES (:username,:email,:role,:password,NULL,:status,:date_created,:date_edited,:created_by,:edited_by)");
  $s->execute([':username'=>$_POST['username'],':email'=>$_POST['email'],':role'=>$_POST['role'],':password'=>$hash,':status'=>$_POST['status']??'active',':date_created'=>$a['date_created'],':date_edited'=>$a['date_edited'],':created_by'=>$a['created_by'],':edited_by'=>$a['edited_by']]);
  $id=$conn->lastInsertId(); audit_log('admin',$id,'CREATE',null,['username'=>$_POST['username'],'email'=>$_POST['email'],'role'=>$_POST['role']],$admin);
  $_SESSION['alert']=['type'=>'success','message'=>'Admin created']; header('Location: ../../pages/admin.php'); exit;
}
header('Location: ../../pages/admin.php'); exit;
