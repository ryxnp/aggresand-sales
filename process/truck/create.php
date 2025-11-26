<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/truck.php'); exit;}
$a=audit_on_create($admin); $s=$conn->prepare("INSERT INTO truck (plate_no,capacity,truck_model,status,is_deleted,date_created,date_edited,created_by,edited_by) VALUES (:plate_no,:capacity,:truck_model,:status,0,:date_created,:date_edited,:created_by,:edited_by)");
$s->execute([':plate_no'=>$_POST['plate_no'],':capacity'=>$_POST['capacity'],':truck_model'=>$_POST['truck_model'],':status'=>$_POST['status']??'active',':date_created'=>$a['date_created'],':date_edited'=>$a['date_edited'],':created_by'=>$a['created_by'],':edited_by'=>$a['edited_by']]);
$id=$conn->lastInsertId(); audit_log('truck',$id,'CREATE',null,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Truck created']; header('Location: ../../pages/truck.php'); exit;
