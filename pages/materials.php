<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#materials.php';

/* =========================================================
   CRUD HANDLING
   ========================================================= */
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

        /* ---------- CREATE ---------- */
        if ($action === 'create') {

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

            audit_log('materials', $conn->lastInsertId(), 'CREATE', null, $_POST, $admin);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Material created'];

        /* ---------- UPDATE ---------- */
        } elseif ($action === 'update') {

            if ($id <= 0) {
                throw new Exception('Invalid material ID');
            }

            $oldStmt = $conn->prepare("
                SELECT * FROM materials
                WHERE material_id = :id AND is_deleted = 0
            ");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found');
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

        /* ---------- DELETE (SOFT) ---------- */
        } elseif ($action === 'delete') {

            if ($id <= 0) {
                throw new Exception('Invalid material ID');
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

            audit_log('materials', $id, 'DELETE', null, ['is_deleted' => 1], $admin);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Material deleted'];
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* =========================================================
   LIST + FILTERS
   ========================================================= */

require_once __DIR__ . '/../helpers/alerts.php';

$q            = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';

$sql = "
    SELECT material_id, material_name, unit_price,
           status, date_created, date_edited
    FROM materials
    WHERE is_deleted = 0
";

$params = [];

/* Status filter */
if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

/* Search */
if ($q !== '') {
    $sql .= " AND material_name LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

/* Sorting */
$sql .= " ORDER BY date_edited DESC, date_created DESC";

/* LIMIT only when NOT searching */
if ($q === '') {
    $sql .= " LIMIT 10";
}

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Materials</h2>

    <?php if (!empty($_SESSION['alert'])) { ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php } ?>

    <div class="row">

        <!-- FORM -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="materials-form-title">
                    Add Material
                </div>
                <div class="card-body">
                    <form id="materials-form" method="POST" action="pages/materials.php">
                        <input type="hidden" name="material_id" id="material_id">
                        <input type="hidden" name="action" id="materials_form_action" value="create">

                        <div class="mb-3">
                            <label class="form-label">Material Name</label>
                            <input type="text" name="material_name" id="material_name"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="materials_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" id="materials-submit-btn">Save</button>
                        <button type="button"
                                class="btn btn-secondary d-none"
                                id="materials-cancel-edit-btn">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="me-auto">
                            <?= $q === '' ? 'Recently Added / Edited Materials' : 'Search Results' ?>
                        </span>

                        <form class="d-flex gap-2 materials-filter-form">
                            <input type="text"
                                   name="q"
                                   value="<?= htmlspecialchars($q) ?>"
                                   class="form-control form-control-sm"
                                   placeholder="Search material">

                            <select name="status_filter"
                                    class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="active"   <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>

                            <button class="btn btn-sm btn-outline-primary">
                                Apply
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered mb-0"
                           id="materials-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Last Edited</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows) { ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    No records found.
                                </td>
                            </tr>
                        <?php } else {
                            foreach ($rows as $r) { ?>
                                <tr>
                                    <td><?= (int)$r['material_id'] ?></td>
                                    <td><?= htmlspecialchars($r['material_name']) ?></td>
                                    <td><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= htmlspecialchars($r['date_edited']) ?></td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-secondary materials-btn-edit"
                                                data-id="<?= (int)$r['material_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['material_name'], ENT_QUOTES) ?>"
                                                data-price="<?= htmlspecialchars($r['unit_price'], ENT_QUOTES) ?>"
                                                data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>">
                                            Edit
                                        </button>

                                        <form method="POST"
                                              class="d-inline"
                                              action="pages/materials.php"
                                              onsubmit="return confirm('Delete this material?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="material_id"
                                                   value="<?= (int)$r['material_id'] ?>">
                                            <button class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php }
                        } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
