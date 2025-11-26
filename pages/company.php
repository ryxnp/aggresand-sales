<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#company.php';

/* ---------------------- CRUD HANDLING ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id     = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;

    $name    = trim($_POST['company_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $status  = $_POST['status'] ?? 'active';

    try {
        if ($name === '') {
            throw new Exception('Company name is required');
        }

        if ($action === 'create') {
            // CREATE
            $audit = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO company
                    (company_name, address, contact_no, email, status, is_deleted,
                     date_created, date_edited, created_by, edited_by)
                VALUES
                    (:name, :address, :contact, :email, :status, 0,
                     :created, :edited, :created_by, :edited_by)
            ");

            $stmt->execute([
                ':name'       => $name,
                ':address'    => $address,
                ':contact'    => $contact,
                ':email'      => $email,
                ':status'     => $status,
                ':created'    => $audit['date_created'],
                ':edited'     => $audit['date_edited'],
                ':created_by' => $audit['created_by'],
                ':edited_by'  => $audit['edited_by'],
            ]);

            $newId = (int)$conn->lastInsertId();
            audit_log('company', $newId, 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Company created'];

        } elseif ($action === 'update') {
            // UPDATE
            if ($id <= 0) {
                throw new Exception('Invalid company ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM company WHERE company_id = :id AND is_deleted = 0");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found or already deleted');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE company SET
                    company_name = :name,
                    address      = :address,
                    contact_no   = :contact,
                    email        = :email,
                    status       = :status,
                    date_edited  = :edited,
                    edited_by    = :edited_by
                WHERE company_id = :id AND is_deleted = 0
            ");

            $stmt->execute([
                ':id'        => $id,
                ':name'      => $name,
                ':address'   => $address,
                ':contact'   => $contact,
                ':email'     => $email,
                ':status'    => $status,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('company', $id, 'UPDATE', $oldData, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Company updated'];

        } elseif ($action === 'delete') {
            // DELETE (soft)
            if ($id <= 0) {
                throw new Exception('Invalid company ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM company WHERE company_id = :id AND is_deleted = 0");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found or already deleted');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE company SET
                    is_deleted  = 1,
                    date_edited = :edited,
                    edited_by   = :edited_by
                WHERE company_id = :id
            ");

            $stmt->execute([
                ':id'        => $id,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('company', $id, 'DELETE', $oldData, ['is_deleted' => 1], $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Company deleted'];
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* ---------------------- DISPLAY DATA (GET) ---------------------- */

require_once __DIR__ . '/../helpers/alerts.php';

$q            = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';

$where  = 'is_deleted = 0';
$params = [];

if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $where .= ' AND status = :status';
    $params[':status'] = $statusFilter;
}

if ($q !== '') {
    $where .= ' AND (company_name LIKE :q OR address LIKE :q OR contact_no LIKE :q OR email LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$per_page     = 5;
$current_page = isset($_GET['p']) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$countSql  = "SELECT COUNT(*) FROM company WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $per_page;

$listSql = "
    SELECT * FROM company
    WHERE $where
    ORDER BY date_created DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($listSql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryForPagination = http_build_query([
    'q'             => $q,
    'status_filter' => $statusFilter,
]);
?>

<div class="container-fluid">

    <h2 class="mb-4">Companies</h2>

    <?php if (!empty($_SESSION['alert'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="row">

        <!-- FORM COLUMN -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="company-form-title">Add Company</div>
                <div class="card-body">
                    <form method="POST" id="company-form" action="pages/company.php">
                        <input type="hidden" name="company_id" id="company_id" value="">
                        <input type="hidden" name="action" id="company_form_action" value="create">

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

                        <button type="submit" class="btn btn-primary" id="company-submit-btn">Save</button>
                        <button type="button" class="btn btn-secondary d-none" id="company-cancel-edit-btn">Cancel</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE COLUMN -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="me-auto">Company List</span>

                        <form class="d-flex flex-wrap gap-2 company-filter-form" method="GET" action="">
                            <input type="text"
                                   name="q"
                                   value="<?= htmlspecialchars($q) ?>"
                                   class="form-control form-control-sm" style="width: 220px;"
                                   placeholder="Search Company">
                                   

                            <select name="status_filter" class="form-select form-select-sm" style="width: 220px;">
                                <option value="">All Status</option>
                                <option value="active"   <?= $statusFilter === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>

                            <button class="btn btn-sm btn-outline-primary">Apply</button>
                        </form>
                    </div>
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
                            <tr><td colspan="8" class="text-center">No records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= (int)$r['company_id'] ?></td>
                                    <td class="col-name"><?= htmlspecialchars($r['company_name']) ?></td>
                                    <td class="col-address"><?= htmlspecialchars($r['address']) ?></td>
                                    <td class="col-contact"><?= htmlspecialchars($r['contact_no']) ?></td>
                                    <td class="col-email"><?= htmlspecialchars($r['email']) ?></td>
                                    <td class="col-status"><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= htmlspecialchars($r['date_created']) ?></td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-secondary company-btn-edit"
                                                data-id="<?= (int)$r['company_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['company_name'], ENT_QUOTES) ?>"
                                                data-address="<?= htmlspecialchars($r['address'], ENT_QUOTES) ?>"
                                                data-contact="<?= htmlspecialchars($r['contact_no'], ENT_QUOTES) ?>"
                                                data-email="<?= htmlspecialchars($r['email'], ENT_QUOTES) ?>"
                                                data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>">
                                            Edit
                                        </button>

                                        <form method="POST" class="d-inline"
                                              action="pages/company.php"
                                              onsubmit="return confirm('Delete this company?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="company_id" value="<?= (int)$r['company_id'] ?>">
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
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

</div>
