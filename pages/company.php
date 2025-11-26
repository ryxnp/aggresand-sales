<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/alerts.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#company.php';   // where we always go after POST

// ---------- HANDLE POST: CREATE / UPDATE / DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $company_name = trim($_POST['company_name'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $contact_no   = trim($_POST['contact_no'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $status       = $_POST['status'] ?? 'active';
    $id           = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;

    try {
        if ($action === 'delete') {
            // ----- DELETE (soft) -----
            if ($id <= 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid company ID'];
            } else {
                $oldStmt = $conn->prepare("SELECT * FROM company WHERE company_id = :id AND is_deleted = 0");
                $oldStmt->execute([':id' => $id]);
                $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldData) {
                    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Record not found or already deleted'];
                } else {
                    $audit = audit_on_update($admin);

                    $stmt = $conn->prepare("
                        UPDATE company
                        SET is_deleted = 1,
                            date_edited = :date_edited,
                            edited_by   = :edited_by
                        WHERE company_id = :id
                    ");
                    $stmt->execute([
                        ':date_edited' => $audit['date_edited'],
                        ':edited_by'   => $audit['edited_by'],
                        ':id'          => $id,
                    ]);

                    audit_log('company', $id, 'DELETE', $oldData, ['is_deleted' => 1], $admin);
                    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Company deleted'];
                }
            }

        } else {
            // CREATE or UPDATE share validation
            if ($company_name === '') {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Company name is required'];
            } else {
                if ($id > 0 && $action === 'update') {
                    // ----- UPDATE -----
                    $oldStmt = $conn->prepare("SELECT * FROM company WHERE company_id = :id AND is_deleted = 0");
                    $oldStmt->execute([':id' => $id]);
                    $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$oldData) {
                        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Record not found or already deleted'];
                    } else {
                        $audit = audit_on_update($admin);

                        $stmt = $conn->prepare("
                            UPDATE company
                            SET company_name = :company_name,
                                address      = :address,
                                contact_no   = :contact_no,
                                email        = :email,
                                status       = :status,
                                date_edited  = :date_edited,
                                edited_by    = :edited_by
                            WHERE company_id = :id AND is_deleted = 0
                        ");
                        $stmt->execute([
                            ':company_name' => $company_name,
                            ':address'      => $address,
                            ':contact_no'   => $contact_no,
                            ':email'        => $email,
                            ':status'       => $status,
                            ':date_edited'  => $audit['date_edited'],
                            ':edited_by'    => $audit['edited_by'],
                            ':id'           => $id,
                        ]);

                        audit_log('company', $id, 'UPDATE', $oldData, $_POST, $admin);

                        if ($stmt->rowCount() > 0) {
                            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Company updated'];
                        } else {
                            $_SESSION['alert'] = ['type' => 'warning', 'message' => 'No changes made'];
                        }
                    }

                } else {
                    // ----- CREATE -----
                    $audit = audit_on_create($admin);

                    $stmt = $conn->prepare("
                        INSERT INTO company
                            (company_name, address, contact_no, email, status, is_deleted,
                             date_created, date_edited, created_by, edited_by)
                        VALUES
                            (:company_name, :address, :contact_no, :email, :status, 0,
                             :date_created, :date_edited, :created_by, :edited_by)
                    ");
                    $stmt->execute([
                        ':company_name' => $company_name,
                        ':address'      => $address,
                        ':contact_no'   => $contact_no,
                        ':email'        => $email,
                        ':status'       => $status,
                        ':date_created' => $audit['date_created'],
                        ':date_edited'  => $audit['date_edited'],
                        ':created_by'   => $audit['created_by'],
                        ':edited_by'    => $audit['edited_by'],
                    ]);

                    $newId = $conn->lastInsertId();
                    audit_log('company', $newId, 'CREATE', null, $_POST, $admin);
                    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Company created'];
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error: '.$e->getMessage()];
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// ---------- READ + PAGINATION ----------
$per_page     = 2;
$current_page = isset($_GET['p']) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$total_records = (int)$conn
    ->query("SELECT COUNT(*) FROM company WHERE is_deleted = 0")
    ->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $per_page;

$stmt = $conn->prepare("
    SELECT * FROM company
    WHERE is_deleted = 0
    ORDER BY date_created DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Original form has empty values by default (we'll fill via JS when editing)
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Companies</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container-fluid">

    <h2 class="mb-4">Companies</h2>

    <?php
    if (!empty($_SESSION['alert'])) {
        $a = $_SESSION['alert'];
        echo '<div class="alert alert-'.htmlspecialchars($a['type']).' alert-dismissible fade show" role="alert">'
            .htmlspecialchars($a['message']).
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
        unset($_SESSION['alert']);
    }
    ?>

    <div class="row">
        <!-- FORM COLUMN -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="form-title">Add Company</div>
                <div class="card-body">
                    <form method="POST" id="company-form" action="/pages/company.php">
                        <input type="hidden" name="company_id" id="company_id" value="">
                        <input type="hidden" name="action" id="form_action" value="create">

                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" id="company_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" id="address" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact No</label>
                            <input type="text" name="contact_no" id="contact_no" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submit-btn">Save</button>
                        <button type="button" class="btn btn-secondary d-none" id="cancel-edit-btn">Cancel</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE COLUMN -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                    <span class="me-auto">Company List</span>

                    <input type="text" id="search-input" class="form-control form-control-sm w-auto"
                           placeholder="Search...">

                    <select id="status-filter" class="form-select form-select-sm w-auto">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered mb-0" id="company-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr data-empty="1"><td colspan="8" class="text-center">No records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td class="col-id"><?= $r['company_id'] ?></td>
                                    <td class="col-name"><?= htmlspecialchars($r['company_name']) ?></td>
                                    <td class="col-address"><?= htmlspecialchars($r['address']) ?></td>
                                    <td class="col-contact"><?= htmlspecialchars($r['contact_no']) ?></td>
                                    <td class="col-email"><?= htmlspecialchars($r['email']) ?></td>
                                    <td class="col-status"><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= htmlspecialchars($r['date_created']) ?></td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-secondary btn-edit"
                                                data-id="<?= $r['company_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['company_name'], ENT_QUOTES) ?>"
                                                data-address="<?= htmlspecialchars($r['address'], ENT_QUOTES) ?>"
                                                data-contact="<?= htmlspecialchars($r['contact_no'], ENT_QUOTES) ?>"
                                                data-email="<?= htmlspecialchars($r['email'], ENT_QUOTES) ?>"
                                                data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>">
                                            Edit
                                        </button>
                                        <form method="POST" class="d-inline"
                                          action="/pages/company.php"
                                          onsubmit="return confirm('Delete this company?');">
                                            <input type="hidden" name="action" value="delete">
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

                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination mb-0">
                                <?php $base = '/main.php#company.php?p='; ?>
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $base . ($current_page - 1) ?>">&laquo;</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $base . $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $base . ($current_page + 1) ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---------- EDIT INLINE WITH JS ----------
const form          = document.getElementById('company-form');
const formTitle     = document.getElementById('form-title');
const submitBtn     = document.getElementById('submit-btn');
const cancelBtn     = document.getElementById('cancel-edit-btn');
const hiddenId      = document.getElementById('company_id');
const formAction    = document.getElementById('form_action');

const nameInput     = document.getElementById('company_name');
const addrInput     = document.getElementById('address');
const contactInput  = document.getElementById('contact_no');
const emailInput    = document.getElementById('email');
const statusSelect  = document.getElementById('status');

function resetForm() {
    hiddenId.value = '';
    formAction.value = 'create';
    nameInput.value = '';
    addrInput.value = '';
    contactInput.value = '';
    emailInput.value = '';
    statusSelect.value = 'active';
    formTitle.textContent = 'Add Company';
    submitBtn.textContent = 'Save';
    cancelBtn.classList.add('d-none');
}

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        hiddenId.value     = btn.dataset.id;
        formAction.value   = 'update';
        nameInput.value    = btn.dataset.name || '';
        addrInput.value    = btn.dataset.address || '';
        contactInput.value = btn.dataset.contact || '';
        emailInput.value   = btn.dataset.email || '';
        statusSelect.value = btn.dataset.status || 'active';

        formTitle.textContent = 'Edit Company #' + btn.dataset.id;
        submitBtn.textContent = 'Update';
        cancelBtn.classList.remove('d-none');

        // Scroll up to form if needed
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

cancelBtn.addEventListener('click', resetForm);

// ---------- SEARCH + STATUS FILTER ----------
const searchInput  = document.getElementById('search-input');
const statusFilter = document.getElementById('status-filter');
const tableBody    = document.querySelector('#company-table tbody');
const tableRows    = Array.from(tableBody.querySelectorAll('tr')).filter(
    row => !row.dataset.empty // ignore "No records" row
);

function applyFilters() {
    const text   = searchInput.value.toLowerCase().trim();
    const status = statusFilter.value;

    tableRows.forEach(row => {
        const name    = row.querySelector('.col-name')?.textContent.toLowerCase() || '';
        const address = row.querySelector('.col-address')?.textContent.toLowerCase() || '';
        const contact = row.querySelector('.col-contact')?.textContent.toLowerCase() || '';
        const email   = row.querySelector('.col-email')?.textContent.toLowerCase() || '';
        const rowStatus = row.querySelector('.col-status')?.textContent.trim();

        const matchesText =
            !text ||
            (name + ' ' + address + ' ' + contact + ' ' + email).includes(text);

        const matchesStatus =
            !status || rowStatus === status;

        row.style.display = (matchesText && matchesStatus) ? '' : 'none';
    });
}

searchInput.addEventListener('input', applyFilters);
statusFilter.addEventListener('change', applyFilters);
</script>

</body>
</html>
