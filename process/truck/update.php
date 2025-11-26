<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/truck.php'); exit;}
$id=intval($_POST['truck_id']); $old=$conn->prepare("SELECT * FROM truck WHERE truck_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin); $s=$conn->prepare("UPDATE truck SET plate_no=:plate_no,capacity=:capacity,truck_model=:truck_model,status=:status,date_edited=:date_edited,edited_by=:edited_by WHERE truck_id=:id");
$s->execute([':plate_no'=>$_POST['plate_no'],':capacity'=>$_POST['capacity'],':truck_model'=>$_POST['truck_model'],':status'=>$_POST['status']??'active',':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id]);
audit_log('truck',$id,'UPDATE',$oldData,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Truck updated']; header('Location: ../../pages/truck.php'); exit;
