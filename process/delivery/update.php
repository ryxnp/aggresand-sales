<?php
session_start(); require_once __DIR__.'/../../config/db.php'; require_once __DIR__.'/../../helpers/audit_fields.php'; require_once __DIR__.'/../../helpers/audit.php';
$admin=$_SESSION['admin_id']??null; if(!$admin){ $_SESSION['alert']=['type'=>'danger','message'=>'Not authenticated']; header('Location: ../../pages/delivery.php'); exit;}
$id=intval($_POST['del_id']); $old=$conn->prepare("SELECT * FROM delivery WHERE del_id=:id"); $old->execute([':id'=>$id]); $oldData=$old->fetch(PDO::FETCH_ASSOC);
$a=audit_on_update($admin);
$s=$conn->prepare("UPDATE delivery SET customer_id=:customer_id,delivery_date=:delivery_date,dr_no=:dr_no,truck_id=:truck_id,billing_date=:billing_date,material=:material,quantity=:quantity,unit_price=:unit_price,status=:status,date_edited=:date_edited,edited_by=:edited_by WHERE del_id=:id");
$s->execute([':customer_id'=>$_POST['customer_id']?:null,':delivery_date'=>$_POST['delivery_date'],':dr_no'=>$_POST['dr_no'],':truck_id'=>$_POST['truck_id']?:null,':billing_date'=>$_POST['billing_date'],':material'=>$_POST['material'],':quantity'=>$_POST['quantity'],':unit_price'=>$_POST['unit_price'],':status'=>$_POST['status']??'pending',':date_edited'=>$a['date_edited'],':edited_by'=>$a['edited_by'],':id'=>$id]);
audit_log('delivery',$id,'UPDATE',$oldData,$_POST,$admin); $_SESSION['alert']=['type'=>'success','message'=>'Delivery updated']; header('Location: ../../pages/delivery.php'); exit;
