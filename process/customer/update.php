<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/customer.php'); exit;}
$id=intval($_POST['customer_id']); $old=$conn->prepare("SELECT * FROM customer WHERE customer_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin);
$s=$conn->prepare("UPDATE customer SET company_id=:company_id,contractor_id=:contractor_id,site_id=:site_id,customer_name=:customer_name,contact_no=:contact_no,email=:email,address=:address,status=:status,date_edited=:date_edited,edited_by=:edited_by WHERE customer_id=:id");
$s->execute([':company_id'=>$_POST['company_id']?:null,':contractor_id'=>$_POST['contractor_id']?:null,':site_id'=>$_POST['site_id']?:null,':customer_name'=>$_POST['customer_name'],':contact_no'=>$_POST['contact_no'],':email'=>$_POST['email'],':address'=>$_POST['address'],':status'=>$_POST['status']??'active',':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id]);
audit_log('customer',$id,'UPDATE',$oldData,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Customer updated']; header('Location: ../../pages/customer.php'); exit;
