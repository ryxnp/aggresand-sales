<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#truck.php';

/* ---------------------- CRUD HANDLING ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $action   = $_POST['action'] ?? '';
    $id       = isset($_POST['truck_id']) ? (int)$_POST['truck_id'] : 0;
    $plate    = trim($_POST['plate_no'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $model    = trim($_POST['truck_model'] ?? '');
    $status   = $_POST['status'] ?? 'active';

    try {
        if ($plate === '') {
            throw new Exception('Plate number is required');
        }

        if ($action === 'create') {
            // CREATE
            $audit = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO truck
                    (plate_no, capacity, truck_model, status, is_deleted,
                     date_created, date_edited, created_by, edited_by)
                VALUES
                    (:plate, :capacity, :model, :status, 0,
                     :created, :edited, :created_by, :edited_by)
            ");

            $stmt->execute([
                ':plate'      => $plate,
                ':capacity'   => $capacity,
                ':model'      => $model,
                ':status'     => $status,
                ':created'    => $audit['date_created'],
                ':edited'     => $audit['date_edited'],
                ':created_by' => $audit['created_by'],
                ':edited_by'  => $audit['edited_by'],
            ]);

            $newId = (int)$conn->lastInsertId();
            audit_log('truck', $newId, 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Truck created'];

        } elseif ($action === 'update') {
            // UPDATE
            if ($id <= 0) {
                throw new Exception('Invalid truck ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM truck WHERE truck_id = :id AND is_deleted = 0");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found or already deleted');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE truck SET
                    plate_no    = :plate,
                    capacity    = :capacity,
                    truck_model = :model,
                    status      = :status,
                    date_edited = :edited,
                    edited_by   = :edited_by
                WHERE truck_id = :id AND is_deleted = 0
            ");

            $stmt->execute([
                ':id'        => $id,
                ':plate'     => $plate,
                ':capacity'  => $capacity,
                ':model'     => $model,
                ':status'    => $status,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('truck', $id, 'UPDATE', $oldData, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Truck updated'];

        } elseif ($action === 'delete') {
            // DELETE (soft)
            if ($id <= 0) {
                throw new Exception('Invalid truck ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM truck WHERE truck_id = :id AND is_deleted = 0");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found or already deleted');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE truck SET
                    is_deleted  = 1,
                    date_edited = :edited,
                    edited_by   = :edited_by
                WHERE truck_id = :id
            ");

            $stmt->execute([
                ':id'        => $id,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('truck', $id, 'DELETE', $oldData, ['is_deleted' => 1], $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Truck deleted'];
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
    $where .= ' AND (plate_no LIKE :q OR truck_model LIKE :q OR capacity LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$per_page     = 5;
$current_page = isset($_GET['p']) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$countSql  = "SELECT COUNT(*) FROM truck WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $per_page;

$listSql = "
    SELECT * FROM truck
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

    <h2 class="mb-4">Trucks</h2>

    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show">
            <?= $_SESSION['alert']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="row">

        <!-- FORM COLUMN -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="truck-form-title">Add Truck</div>

                <div class="card-body">
                    <form id="truck-form" method="POST" action="pages/truck.php">
                        <input type="hidden" id="truck_id" name="truck_id">
                        <input type="hidden" name="action" id="truck_form_action" value="create">

                        <div class="mb-3">
                            <label class="form-label">Plate No</label>
                            <input type="text" id="plate_no" name="plate_no" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="text" id="capacity" name="capacity" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Truck Model</label>
                            <input type="text" id="truck_model" name="truck_model" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" id="truck-submit-btn">Save</button>
                        <button type="button" class="btn btn-secondary d-none" id="truck-cancel-edit-btn">Cancel</button>

                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE COLUMN -->
        <div class="col-lg-8 mb-4">
            <div class="card">

                <div class="card-header d-flex align-items-center gap-2 flex-nowrap">
                    <span class="me-auto">Truck List</span>

                    <form class="d-flex gap-2 truck-filter-form" method="GET" action="">
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="Search Truck" style="width: 220px;">
                        
                        <select name="status_filter" class="form-select form-select-sm" style="width: 220px;">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>

                        <button class="btn btn-sm btn-outline-primary">Apply</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Plate</th>
                                <th>Capacity</th>
                                <th>Model</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7" class="text-center">No records found</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= $r['truck_id'] ?></td>
                                    <td class="col-plate"><?= htmlspecialchars($r['plate_no']) ?></td>
                                    <td class="col-capacity"><?= htmlspecialchars($r['capacity']) ?></td>
                                    <td class="col-model"><?= htmlspecialchars($r['truck_model']) ?></td>
                                    <td class="col-status"><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= htmlspecialchars($r['date_created']) ?></td>
                                    <td class="text-nowrap">

                                        <button
                                            class="btn btn-sm btn-secondary truck-btn-edit"
                                            data-id="<?= $r['truck_id'] ?>"
                                            data-plate="<?= htmlspecialchars($r['plate_no'], ENT_QUOTES) ?>"
                                            data-capacity="<?= htmlspecialchars($r['capacity'], ENT_QUOTES) ?>"
                                            data-model="<?= htmlspecialchars($r['truck_model'], ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="pages/truck.php"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete this truck?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="truck_id" value="<?= $r['truck_id'] ?>">
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
                                    <a class="page-link" href="?<?= $queryBase ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= $queryBase ?>&p=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= $queryBase ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                                </li>

                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

</div>
