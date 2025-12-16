<?php
/**
 * createuser.php
 * Handles creation of admin / supervisor / encoder accounts
 * SAFE: PDO, prepared statements, FK-safe
 */

session_start();

/* =========================
   SECURITY: ADMIN ONLY
========================= */
if (!isset($_SESSION['admin_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    die('Access denied.');
}

/* =========================
   DB CONNECTION
========================= */
require_once __DIR__ . '/config/db.php'; // adjust if path differs

$error = '';
$success = '';

/* =========================
   HANDLE FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $role             = $_POST['role'] ?? '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /* =========================
       VALIDATION
    ========================= */
    if (
        $username === '' ||
        $email === '' ||
        $role === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = 'All fields are required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    }
    elseif (!in_array($role, ['Admin', 'Supervisor', 'Encoder'], true)) {
        $error = 'Invalid role selected.';
    }
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }
    elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    }
    else {

        /* =========================
           CHECK DUPLICATES
        ========================= */
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM admin 
            WHERE username = :username OR email = :email
        ");
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email
        ]);

        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        }
        else {

            /* =========================
               CREATE ACCOUNT
            ========================= */
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO admin (
                    username,
                    email,
                    role,
                    password,
                    status,
                    date_created,
                    created_by
                ) VALUES (
                    :username,
                    :email,
                    :role,
                    :password,
                    'Active',
                    NOW(),
                    :created_by
                )
            ");

            $successInsert = $stmt->execute([
                ':username'   => $username,
                ':email'      => $email,
                ':role'       => $role,
                ':password'   => $hashedPassword,
                ':created_by' => (int) $_SESSION['admin_id'] // MUST be INT
            ]);

            if ($successInsert) {
                $success = 'User account created successfully.';
            } else {
                $error = 'Failed to create user account.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">

                    <h4 class="text-center mb-4">Create User Account</h4>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select role</option>
                                <option value="Admin">Admin</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Encoder">Encoder</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                Create Account
                            </button>
                        </div>

                    </form>

                    <p class="text-center mt-3">
                        <a href="index.php">Back to Dashboard</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
