<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// ----------------------------
// Handle form submission
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $role       = trim($_POST['role']);
    $password   = trim($_POST['password']);
    $created_by = $_SESSION['user_id'];

    if ($username && $email && $role && $password) {
        try {
            $stmt = $conn->prepare("INSERT INTO user_account 
                (username, email, role, password, status, created_by, date_created)
                VALUES (:username, :email, :role, :password, 'active', :created_by, NOW())");
            $stmt->execute([
                ':username'   => $username,
                ':email'      => $email,
                ':role'       => $role,
                ':password'   => password_hash($password, PASSWORD_DEFAULT),
                ':created_by' => $created_by
            ]);
            $_SESSION['success'] = "Account created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating account: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    // Redirect to reload page and show messages
    header("Location: accounts.php");
    exit;
}

// ----------------------------
// Fetch accounts for table
// ----------------------------
try {
    $stmt = $conn->query("SELECT user_id, username, email, role, status, last_login, date_created 
                          FROM user_account ORDER BY date_created DESC");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $accounts = [];
    $error_msg = "Error loading accounts: " . $e->getMessage();
}
?>

<div class="container mt-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>User Accounts</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            Create User
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
            <tbody>
                <?php if($accounts): ?>
                    <?php foreach($accounts as $acc): ?>
                        <tr>
                            <td><?= htmlspecialchars($acc['user_id']) ?></td>
                            <td><?= htmlspecialchars($acc['username']) ?></td>
                            <td><?= htmlspecialchars($acc['email']) ?></td>
                            <td><?= htmlspecialchars($acc['role']) ?></td>
                            <td><?= htmlspecialchars($acc['status']) ?></td>
                            <td><?= $acc['last_login'] ?: '—' ?></td>
                            <td><?= htmlspecialchars($acc['date_created']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted">No accounts found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Form posts directly to this same page -->
            <form method="POST" action="../config/process_account.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden dummy password to prevent autofill -->
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
                            <option value="SuperAdmin">SuperAdmin</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Encoder">Encoder</option>
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
                    <button type="submit" name="create_user" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
