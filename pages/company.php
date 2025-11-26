<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/alerts.php';

$total_records = $conn->query("SELECT COUNT(*) FROM company WHERE is_deleted = 0")->fetchColumn();
$limit = 10;
require __DIR__ . '/../helpers/pagination.php';

$stmt = $conn->prepare("SELECT * FROM company WHERE is_deleted = 0 ORDER BY date_created DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit',$pagination['limit'],PDO::PARAM_INT);
$stmt->bindValue(':offset',$pagination['offset'],PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html><head>
<meta charset="utf-8"><title>Companies</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<div class="container">
  <h2>Companies</h2>
  <?php require __DIR__ . '/../helpers/alerts.php'; ?>

  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Add Company</button>

  <div class="table-responsive">
  <table class="table table-striped table-bordered">
    <thead class="table-light"><tr>
      <th>ID</th><th>Name</th><th>Address</th><th>Contact</th><th>Email</th><th>Status</th><th>Created</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8" class="text-center">No records</td></tr>
      <?php else: ?>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['company_id']) ?></td>
          <td><?= htmlspecialchars($r['company_name']) ?></td>
          <td><?= htmlspecialchars($r['address']) ?></td>
          <td><?= htmlspecialchars($r['contact_no']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= htmlspecialchars($r['status']) ?></td>
          <td><?= htmlspecialchars($r['date_created']) ?></td>
          <td style="white-space:nowrap">
            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal"
              data-id="<?= $r['company_id'] ?>"
              data-company_name="<?= htmlspecialchars($r['company_name'],ENT_QUOTES) ?>"
              data-address="<?= htmlspecialchars($r['address'],ENT_QUOTES) ?>"
              data-contact_no="<?= htmlspecialchars($r['contact_no'],ENT_QUOTES) ?>"
              data-email="<?= htmlspecialchars($r['email'],ENT_QUOTES) ?>"
              data-status="<?= htmlspecialchars($r['status'],ENT_QUOTES) ?>"
            >Edit</button>
            <form action="../process/company/delete.php" method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
              <input type="hidden" name="company_id" value="<?= $r['company_id'] ?>">
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>

  <?php include __DIR__ . '/../helpers/pagination.php'; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="../process/company/create.php" method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Company Name</label><input class="form-control" name="company_name" required></div>
        <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="address"></div>
        <div class="mb-3"><label class="form-label">Contact No</label><input class="form-control" name="contact_no"></div>
        <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" type="email"></div>
        <div class="mb-3"><label class="form-label">Status</label>
          <select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      <button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="../process/company/update.php" method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="company_id" id="edit_company_id">
        <div class="mb-3"><label class="form-label">Company Name</label><input id="edit_company_name" class="form-control" name="company_name" required></div>
        <div class="mb-3"><label class="form-label">Address</label><input id="edit_address" class="form-control" name="address"></div>
        <div class="mb-3"><label class="form-label">Contact No</label><input id="edit_contact_no" class="form-control" name="contact_no"></div>
        <div class="mb-3"><label class="form-label">Email</label><input id="edit_email" class="form-control" name="email" type="email"></div>
        <div class="mb-3"><label class="form-label">Status</label>
          <select id="edit_status" name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      <button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('edit_company_id').value = btn.getAttribute('data-id');
  document.getElementById('edit_company_name').value = btn.getAttribute('data-company_name') || '';
  document.getElementById('edit_address').value = btn.getAttribute('data-address') || '';
  document.getElementById('edit_contact_no').value = btn.getAttribute('data-contact_no') || '';
  document.getElementById('edit_email').value = btn.getAttribute('data-email') || '';
  document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
});
</script>
</body></html>
