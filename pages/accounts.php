<?php
if (!defined('IN_DASHBOARD')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied.');
}

require_once __DIR__ . '/../include/db.php';
session_start();

// ------------------------
// Handle AJAX registration
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    header('Content-Type: application/json');

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $status = 'active';
    $created_by = $_SESSION['user_id'] ?? 1;
    $date_created = date('Y-m-d H:i:s');

    if ($username && $email && $role && $password) {
        try {
            $stmt = $conn->prepare("INSERT INTO User_account 
                (username, email, role, password, status, created_by, date_created)
                VALUES (:username, :email, :role, :password, :status, :created_by, :date_created)");

            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':role' => $role,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':status' => $status,
                ':created_by' => $created_by,
                ':date_created' => $date_created
            ]);

            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "All fields are required."]);
    }
    exit;
}

// ------------------------
// Fetch accounts table
// ------------------------
function fetchAccountsTable($conn) {
    try {
        $stmt = $conn->query("SELECT user_id, username, email, role, status, last_login, date_created 
                              FROM User_account ORDER BY date_created DESC");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($accounts) {
            foreach ($accounts as $acc) {
                echo "<tr>
                        <td>{$acc['user_id']}</td>
                        <td>{$acc['username']}</td>
                        <td>{$acc['email']}</td>
                        <td>{$acc['role']}</td>
                        <td>{$acc['status']}</td>
                        <td>" . ($acc['last_login'] ?: '—') . "</td>
                        <td>{$acc['date_created']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7' class='text-center text-muted'>No accounts found.</td></tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='7' class='text-danger'>Error loading accounts: {$e->getMessage()}</td></tr>";
    }
}
?>

<!-- Create User Button -->
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        Create User
    </button>
</div>

<!-- Accounts Table -->
<div class="table-responsive accounts-container">
    <table class="table table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Date Created</th>
            </tr>
        </thead>
        <tbody id="accountsTable">
            <?php fetchAccountsTable($conn); ?>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createUserForm" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden dummy password to prevent browser autofill -->
                    <input type="password" style="display:none">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required 
                               autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
