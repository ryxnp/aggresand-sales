<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/customer.php'); exit;}
$a=audit_on_create($admin);
$s=$conn->prepare("INSERT INTO customer (company_id,contractor_id,site_id,customer_name,contact_no,email,address,status,is_deleted,date_created,date_edited,created_by,edited_by) VALUES (:company_id,:contractor_id,:site_id,:customer_name,:contact_no,:email,:address,:status,0,:date_created,:date_edited,:created_by,:edited_by)");
$s->execute([
 ':company_id'=>$_POST['company_id']?:null, ':contractor_id'=>$_POST['contractor_id']?:null, ':site_id'=>$_POST['site_id']?:null,
 ':customer_name'=>$_POST['customer_name'], ':contact_no'=>$_POST['contact_no'], ':email'=>$_POST['email'], ':address'=>$_POST['address'],
 ':status'=>$_POST['status']??'active', ':date_created'=>$a['date_created'], ':date_edited'=>$a['date_edited'], ':created_by'=>$a['created_by'], ':edited_by'=>$a['edited_by']
]);
$id=$conn->lastInsertId(); audit_log('customer',$id,'CREATE',null,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Customer created']; header('Location: ../../pages/customer.php'); exit;
