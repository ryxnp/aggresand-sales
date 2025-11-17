<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch contractors (exclude deleted)
try {
    $stmt = $conn->query("SELECT contractor_id, contractor_name, contact_person, contact_no, email, status, date_created, date_edited, edited_by 
                          FROM contractor 
                          WHERE is_deleted = 0 
                          ORDER BY date_created DESC");
    $contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contractors = [];
    $error_msg = "Error loading contractors: " . $e->getMessage();
}
?>

<div class="container mt-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Contractors</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createContractorModal">
            Add Contractor
        </button>
    </div>

    <!-- Messages -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if(isset($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg; ?></div>
    <?php endif; ?>

    <!-- Contractors Table -->
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Contractor Name</th>
                    <th>Contact Person</th>
                    <th>Contact No</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($contractors): ?>
                    <?php foreach($contractors as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['contractor_name']) ?></td>
                            <td><?= htmlspecialchars($c['contact_person']) ?></td>
                            <td><?= htmlspecialchars($c['contact_no']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['status']) ?></td>
                            <td>
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-warning editBtn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editContractorModal"
                                        data-id="<?= $c['contractor_id'] ?>"
                                        data-name="<?= htmlspecialchars($c['contractor_name'], ENT_QUOTES) ?>"
                                        data-person="<?= htmlspecialchars($c['contact_person'], ENT_QUOTES) ?>"
                                        data-contact="<?= htmlspecialchars($c['contact_no'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($c['email'], ENT_QUOTES) ?>"
                                        data-status="<?= $c['status'] ?>">
                                    Edit
                                </button>

                                <!-- Delete Form -->
                                <form method="POST" action="../config/process_contractor.php" style="display:inline-block">
                                    <input type="hidden" name="contractor_id" value="<?= $c['contractor_id'] ?>">
                                    <button type="submit" name="delete_contractor" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this contractor?');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No contractors found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Contractor Modal -->
<div class="modal fade" id="createContractorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="../config/process_contractor.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contractor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contractor Name</label>
                        <input type="text" name="contractor_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_contractor" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contractor Modal -->
<div class="modal fade" id="editContractorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="../config/process_contractor.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Contractor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_contractor_id" name="contractor_id">
                    <div class="mb-3">
                        <label class="form-label">Contractor Name</label>
                        <input type="text" id="edit_contractor_name" name="contractor_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" id="edit_contact_person" name="contact_person" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" id="edit_contact_no" name="contact_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="edit_status" name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_contractor" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModalEl = document.getElementById('editContractorModal');
    if (!editModalEl) return;

    editModalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        document.getElementById('edit_contractor_id').value = button.getAttribute('data-id') || '';
        document.getElementById('edit_contractor_name').value = button.getAttribute('data-name') || '';
        document.getElementById('edit_contact_person').value = button.getAttribute('data-person') || '';
        document.getElementById('edit_contact_no').value = button.getAttribute('data-contact') || '';
        document.getElementById('edit_email').value = button.getAttribute('data-email') || '';
        document.getElementById('edit_status').value = button.getAttribute('data-status') || 'active';
    });
});
</script>
