<?php
session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../helpers/alerts.php';
$total_records = $conn->query("SELECT COUNT(*) FROM contractor WHERE is_deleted = 0")->fetchColumn();
$limit=10; require __DIR__.'/../helpers/pagination.php';
$stmt = $conn->prepare("SELECT * FROM contractor WHERE is_deleted=0 ORDER BY date_created DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit',$pagination['limit'],PDO::PARAM_INT);
$stmt->bindValue(':offset',$pagination['offset'],PDO::PARAM_INT);
$stmt->execute(); $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Contractors</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container">
<h2>Contractors</h2>
<?php require __DIR__.'/../helpers/alerts.php'; ?>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Add Contractor</button>
<div class="table-responsive"><table class="table table-striped table-bordered"><thead class="table-light"><tr>
<th>ID</th><th>Name</th><th>Contact Person</th><th>Contact No</th><th>Email</th><th>Status</th><th>Created</th><th>Actions</th>
</tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="8" class="text-center">No records</td></tr><?php else:
foreach($rows as $r): ?>
<tr>
<td><?=htmlspecialchars($r['contractor_id'])?></td>
<td><?=htmlspecialchars($r['contractor_name'])?></td>
<td><?=htmlspecialchars($r['contact_person'])?></td>
<td><?=htmlspecialchars($r['contact_no'])?></td>
<td><?=htmlspecialchars($r['email'])?></td>
<td><?=htmlspecialchars($r['status'])?></td>
<td><?=htmlspecialchars($r['date_created'])?></td>
<td style="white-space:nowrap">
<button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal"
 data-id="<?= $r['contractor_id'] ?>"
 data-contractor_name="<?= htmlspecialchars($r['contractor_name'],ENT_QUOTES) ?>"
 data-contact_person="<?= htmlspecialchars($r['contact_person'],ENT_QUOTES) ?>"
 data-contact_no="<?= htmlspecialchars($r['contact_no'],ENT_QUOTES) ?>"
 data-email="<?= htmlspecialchars($r['email'],ENT_QUOTES) ?>"
 data-status="<?= htmlspecialchars($r['status'],ENT_QUOTES) ?>">Edit</button>
<form action="../process/contractor/delete.php" method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
<input type="hidden" name="contractor_id" value="<?= $r['contractor_id'] ?>">
<button class="btn btn-sm btn-danger">Delete</button></form>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php include __DIR__.'/../helpers/pagination.php'; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog">
<form action="../process/contractor/create.php" method="POST" class="modal-content">
<div class="modal-header"><h5 class="modal-title">Add Contractor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
  <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="contractor_name" required></div>
  <div class="mb-3"><label class="form-label">Contact Person</label><input class="form-control" name="contact_person"></div>
  <div class="mb-3"><label class="form-label">Contact No</label><input class="form-control" name="contact_no"></div>
  <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" type="email"></div>
  <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Save</button></div>
</form></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog">
<form action="../process/contractor/update.php" method="POST" class="modal-content">
<div class="modal-header"><h5 class="modal-title">Edit Contractor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="contractor_id" id="edit_contractor_id">
<div class="mb-3"><label class="form-label">Name</label><input id="edit_contractor_name" class="form-control" name="contractor_name" required></div>
<div class="mb-3"><label class="form-label">Contact Person</label><input id="edit_contact_person" class="form-control" name="contact_person"></div>
<div class="mb-3"><label class="form-label">Contact No</label><input id="edit_contact_no" class="form-control" name="contact_no"></div>
<div class="mb-3"><label class="form-label">Email</label><input id="edit_email" class="form-control" name="email" type="email"></div>
<div class="mb-3"><label class="form-label">Status</label><select id="edit_status" name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Update</button></div>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', function(e){
 var btn = e.relatedTarget;
 document.getElementById('edit_contractor_id').value = btn.getAttribute('data-id');
 document.getElementById('edit_contractor_name').value = btn.getAttribute('data-contractor_name') || '';
 document.getElementById('edit_contact_person').value = btn.getAttribute('data-contact_person') || '';
 document.getElementById('edit_contact_no').value = btn.getAttribute('data-contact_no') || '';
 document.getElementById('edit_email').value = btn.getAttribute('data-email') || '';
 document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
});
</script>
</body></html>
