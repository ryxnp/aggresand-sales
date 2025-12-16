<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#materials.php';

/* ---------------------- CRUD HANDLING ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $action = $_POST['action'] ?? 'create';
    $id     = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;

    $name   = trim($_POST['material_name'] ?? '');
    $price  = (float)($_POST['unit_price'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    try {
        if ($name === '') {
            throw new Exception('Material name is required');
        }

        if ($action === 'create') {
            // CREATE
            $audit = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO materials
                    (material_name, unit_price, status, is_deleted,
                     date_created, date_edited, created_by, edited_by)
                VALUES
                    (:name, :price, :status, 0,
                     :created, :edited, :created_by, :edited_by)
            ");

            $stmt->execute([
                ':name'       => $name,
                ':price'      => $price,
                ':status'     => $status,
                ':created'    => $audit['date_created'],
                ':edited'     => $audit['date_edited'],
                ':created_by' => $audit['created_by'],
                ':edited_by'  => $audit['edited_by'],
            ]);

            $newId = (int)$conn->lastInsertId();
            audit_log('materials', $newId, 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Material created'];

        } elseif ($action === 'update') {
            // UPDATE
            if ($id <= 0) {
                throw new Exception('Invalid material ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM materials WHERE material_id = :id AND is_deleted = 0");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found or already deleted');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE materials SET
                    material_name = :name,
                    unit_price    = :price,
                    status        = :status,
                    date_edited   = :edited,
                    edited_by     = :edited_by
                WHERE material_id = :id AND is_deleted = 0
            ");

            $stmt->execute([
                ':id'        => $id,
                ':name'      => $name,
                ':price'     => $price,
                ':status'    => $status,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('materials', $id, 'UPDATE', $oldData, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Material updated'];

        } elseif ($action === 'delete') {
            // SOFT DELETE
            if ($id <= 0) {
                throw new Exception('Invalid material ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM materials WHERE material_id = :id AND is_deleted = 0");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found or already deleted');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE materials SET
                    is_deleted  = 1,
                    date_edited = :edited,
                    edited_by   = :edited_by
                WHERE material_id = :id
            ");

            $stmt->execute([
                ':id'        => $id,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('materials', $id, 'DELETE', $oldData, ['is_deleted' => 1], $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Material deleted'];
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* ---------------------- LIST + FILTERS ---------------------- */

require_once __DIR__ . '/../helpers/alerts.php';

$q            = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';

$where  = "is_deleted = 0";
$params = [];

// status filter
if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $where .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

// search text on name or price
if ($q !== '') {
    $where .= " AND (material_name LIKE :q OR unit_price LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

// pagination
$per_page     = 10;
$current_page = isset($_GET['p']) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// count
$countSql = "SELECT COUNT(*) FROM materials WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $per_page;

// list
$listSql = "
    SELECT material_id, material_name, unit_price, status, date_created
    FROM materials
    WHERE $where
    ORDER BY material_name ASC
    LIMIT :limit OFFSET :offset
";

$listStmt = $conn->prepare($listSql);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$queryForPagination = http_build_query([
    'q'             => $q,
    'status_filter' => $statusFilter,
]);
?>

<div class="container-fluid">
    <h2 class="mb-4">Materials</h2>

    <?php if (!empty($_SESSION['alert'])) { ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php } ?>

    <div class="row">
        <!-- FORM COLUMN -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="materials-form-title">Add Material</div>
                <div class="card-body">
                    <form id="materials-form" method="POST" action="pages/materials.php">
                        <input type="hidden" name="material_id" id="material_id">
                        <input type="hidden" name="action" id="materials_form_action" value="create">

                        <div class="mb-3">
                            <label class="form-label">Material Name</label>
                            <input type="text" name="material_name" id="material_name" class="form-control" required>
                        </div>

                        <!-- <div class="mb-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" required>
                        </div> -->

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="materials_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" id="materials-submit-btn">Save</button>
                        <button type="button" class="btn btn-secondary d-none" id="materials-cancel-edit-btn">Cancel</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE COLUMN -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="me-auto">Material List</span>

                        <form class="d-flex flex-wrap gap-2 materials-filter-form" method="GET" action="">
                            <input type="text"
                                   name="q"
                                   value="<?= htmlspecialchars($q) ?>"
                                   class="form-control form-control-sm"
                                   style="width: 220px;"
                                   placeholder="Search Material">

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
                    <table class="table table-striped table-bordered mb-0" id="materials-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <!-- <th>Unit Price</th> -->
                                <th>Status</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows) { ?>
                            <tr><td colspan="6" class="text-center">No records found.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($rows as $r) { ?>
                                <tr>
                                    <td><?= (int)$r['material_id'] ?></td>
                                    <td class="col-name"><?= htmlspecialchars($r['material_name']) ?></td>
                                    <!-- <td class="col-price"><?= number_format((float)$r['unit_price'], 2) ?></td> -->
                                    <td class="col-status"><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= htmlspecialchars($r['date_created']) ?></td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-secondary materials-btn-edit"
                                                data-id="<?= (int)$r['material_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['material_name'], ENT_QUOTES) ?>"
                                                data-price="<?= htmlspecialchars($r['unit_price'], ENT_QUOTES) ?>"
                                                data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>">
                                            Edit
                                        </button>

                                        <form method="POST" class="d-inline"
                                              action="pages/materials.php"
                                              onsubmit="return confirm('Delete this material?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="material_id" value="<?= (int)$r['material_id'] ?>">
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1) { ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination mb-0">
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php } ?>

                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="?<?= htmlspecialchars($queryForPagination) ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
