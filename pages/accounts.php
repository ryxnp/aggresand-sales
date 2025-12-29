<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';

$admin       = $_SESSION['admin_id'] ?? null;
$role        = $_SESSION['role'] ?? '';
$redirectUrl = '/main.php#accounts.php';

/* ---------------------- CRUD HANDLING ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = $_POST['action'] ?? '';
    $userId   = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $roleVal  = $_POST['role'] ?? '';
    $status   = $_POST['status'] ?? 'Active';
    $password = $_POST['password'] ?? '';

    try {
        if ($username === '' || $email === '') {
            throw new Exception('Username and email are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        if (!in_array($roleVal, ['Admin', 'Supervisor', 'Encoder'], true)) {
            throw new Exception('Invalid role');
        }

        /* ---------- CREATE ---------- */
        if ($action === 'create') {

            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters');
            }

            $audit = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO admin
                    (username, email, role, password, status,
                     date_created, date_edited, created_by, edited_by)
                VALUES
                    (:username, :email, :role, :password, :status,
                     :created, :edited, :created_by, :edited_by)
            ");

            $stmt->execute([
                ':username'   => $username,
                ':email'      => $email,
                ':role'       => $roleVal,
                ':password'   => password_hash($password, PASSWORD_DEFAULT),
                ':status'     => $status,
                ':created'    => $audit['date_created'],
                ':edited'     => $audit['date_edited'],
                ':created_by' => $audit['created_by'],
                ':edited_by'  => $audit['edited_by'],
            ]);

            $newId = (int)$conn->lastInsertId();
            audit_log('admin', $newId, 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'User account created'];

        /* ---------- UPDATE ---------- */
        } elseif ($action === 'update') {

            if ($userId <= 0) {
                throw new Exception('Invalid user ID');
            }

            $oldStmt = $conn->prepare("SELECT * FROM admin WHERE admin_id = :id");
            $oldStmt->execute([':id' => $userId]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('User not found');
            }

            $audit = audit_on_update($admin);

            $sql = "
                UPDATE admin SET
                    username    = :username,
                    email       = :email,
                    role        = :role,
                    status      = :status,
                    date_edited = :edited,
                    edited_by   = :edited_by
            ";

            if ($password !== '') {
                $sql .= ", password = :password";
            }

            $sql .= " WHERE admin_id = :id";

            $stmt = $conn->prepare($sql);

            $params = [
                ':id'        => $userId,
                ':username'  => $username,
                ':email'     => $email,
                ':role'      => $roleVal,
                ':status'    => $status,
                ':edited'    => $audit['date_edited'],
                ':edited_by' => $audit['edited_by'],
            ];

            if ($password !== '') {
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $stmt->execute($params);

            audit_log('admin', $userId, 'UPDATE', $oldData, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'User updated'];

        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    }

    header("Location: $redirectUrl");
    exit;
}

/* ---------------------- DISPLAY DATA ---------------------- */

require_once __DIR__ . '/../helpers/alerts.php';

$stmt = $conn->query("
    SELECT admin_id, username, email, role, status, date_created
    FROM admin
    ORDER BY date_created DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">

    <h2 class="mb-4">User Accounts</h2>

    <?php if (!empty($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="row">

        <!-- FORM -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header" id="account-form-title">Add User</div>
                <div class="card-body">
                    <form method="POST" id="account-form" action="pages/accounts.php">
                        <input type="hidden" name="action" id="account_action" value="create">
                        <input type="hidden" name="admin_id" id="admin_id">

                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" id="role" class="form-select">
                                <option value="Admin">Admin</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Encoder">Encoder</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Disabled">Disabled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>

                        <button class="btn btn-primary" id="account-submit-btn">Save</button>
                        <button type="button" class="btn btn-secondary d-none" id="account-cancel-btn">Cancel</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= $r['admin_id'] ?></td>
                                <td><?= htmlspecialchars($r['username']) ?></td>
                                <td><?= htmlspecialchars($r['email']) ?></td>
                                <td><?= htmlspecialchars($r['role']) ?></td>
                                <td><?= htmlspecialchars($r['status']) ?></td>
                                <td><?= htmlspecialchars($r['date_created']) ?></td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-secondary account-edit-btn"
                                        data-id="<?= $r['admin_id'] ?>"
                                        data-username="<?= htmlspecialchars($r['username'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($r['email'], ENT_QUOTES) ?>"
                                        data-role="<?= $r['role'] ?>"
                                        data-status="<?= $r['status'] ?>">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="js/accounts.js"></script>
<script>
    if (window.AccountsPage) {
        AccountsPage.init();
    }
</script>