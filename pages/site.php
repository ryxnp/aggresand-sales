<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#site.php';

/* =========================================================
   CRUD HANDLING
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id     = isset($_POST['site_id']) ? (int)$_POST['site_id'] : 0;

    $name    = trim($_POST['site_name'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $loc     = trim($_POST['location'] ?? '');
    $status  = $_POST['status'] ?? 'active';

    try {

        if (in_array($action, ['create', 'update'], true) && $name === '') {
            throw new Exception('Site name is required');
        }

        /* ---------- CREATE ---------- */
        if ($action === 'create') {

            $audit = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO site
                    (site_name, remarks, location, status, is_deleted,
                     date_created, date_edited, created_by, edited_by)
                VALUES
                    (:name, :remarks, :loc, :status, 0,
                     :created, :edited, :created_by, :edited_by)
            ");

            $stmt->execute([
                ':name'       => $name,
                ':remarks'    => $remarks,
                ':loc'        => $loc,
                ':status'     => $status,
                ':created'    => $audit['date_created'],
                ':edited'     => $audit['date_edited'],
                ':created_by' => $audit['created_by'],
                ':edited_by'  => $audit['edited_by'],
            ]);

            audit_log('site', $conn->lastInsertId(), 'CREATE', null, $_POST, $admin);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Site created'];

        /* ---------- UPDATE ---------- */
        } elseif ($action === 'update') {

            if ($id <= 0) {
                throw new Exception('Invalid site ID');
            }

            $oldStmt = $conn->prepare("
                SELECT * FROM site
                WHERE site_id = :id AND is_deleted = 0
            ");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Record not found');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE site SET
                    site_name   = :name,
                    remarks     = :remarks,
                    location    = :loc,
                    status      = :status,
                    date_edited = :edited,
                    edited_by   = :edited_by
                WHERE site_id = :id AND is_deleted = 0
            ");

            $stmt->execute([
                ':id'        => $id,
                ':name'      => $name,
                ':remarks'   => $remarks,
                ':loc'       => $loc,
                ':status'    => $status,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('site', $id, 'UPDATE', $oldData, $_POST, $admin);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Site updated'];

        /* ---------- DELETE (SOFT) ---------- */
        } elseif ($action === 'delete') {

            if ($id <= 0) {
                throw new Exception('Invalid site ID');
            }

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE site SET
                    is_deleted  = 1,
                    date_edited = :edited,
                    edited_by   = :edited_by
                WHERE site_id = :id
            ");

            $stmt->execute([
                ':id'        => $id,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ]);

            audit_log('site', $id, 'DELETE', null, ['is_deleted' => 1], $admin);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Site deleted'];
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* =========================================================
   LIST + FILTERS (RECENT + SEARCH)
   ========================================================= */

require_once __DIR__ . '/../helpers/alerts.php';

$q            = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';

$sql = "
    SELECT *
    FROM site
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
    $sql .= " AND (site_name LIKE :q OR remarks LIKE :q OR location LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

/* Sort by recent activity */
$sql .= " ORDER BY date_edited DESC, date_created DESC";

/* Limit only when NOT searching */
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

    <h2 class="mb-4">Sites</h2>

    <?php if (!empty($_SESSION['alert'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="row">

        <!-- FORM COLUMN -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="site-form-title">Add Site</div>
                <div class="card-body">
                    <form id="site-form" method="POST" action="pages/site.php">
                        <input type="hidden" id="site_id" name="site_id">
                        <input type="hidden" name="action" id="site_form_action" value="create">

                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" id="site_name" name="site_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" id="remarks" name="remarks" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" id="location" name="location" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" id="site-submit-btn">Save</button>
                        <button type="button" class="btn btn-secondary d-none" id="site-cancel-edit-btn">Cancel</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE COLUMN -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="me-auto">
                        <?= $q === '' ? 'Recently Added / Edited Sites' : 'Search Results' ?>
                    </span>

                    <form class="d-flex gap-2 site-filter-form">
                        <input type="text" name="q"
                               value="<?= htmlspecialchars($q) ?>"
                               class="form-control form-control-sm"
                               placeholder="Search Site" style="width:220px;">

                        <select name="status_filter"
                                class="form-select form-select-sm"
                                style="width:220px;">
                            <option value="">All Status</option>
                            <option value="active"   <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
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
                                <th>Name</th>
                                <th>Remarks</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Last Edited</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7" class="text-center">No records found</td></tr>
                        <?php else:
                            foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= (int)$r['site_id'] ?></td>
                                    <td><?= htmlspecialchars($r['site_name']) ?></td>
                                    <td><?= htmlspecialchars($r['remarks']) ?></td>
                                    <td><?= htmlspecialchars($r['location']) ?></td>
                                    <td><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= htmlspecialchars($r['date_edited']) ?></td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-secondary site-btn-edit"
                                                data-id="<?= (int)$r['site_id'] ?>"
                                                data-name="<?= htmlspecialchars($r['site_name'], ENT_QUOTES) ?>"
                                                data-remarks="<?= htmlspecialchars($r['remarks'], ENT_QUOTES) ?>"
                                                data-location="<?= htmlspecialchars($r['location'], ENT_QUOTES) ?>"
                                                data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>">
                                            Edit
                                        </button>

                                        <form method="POST"
                                              action="pages/site.php"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete this site?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="site_id" value="<?= (int)$r['site_id'] ?>">
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>
