<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit;
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$role = trim($_POST['role']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$status = 'Active';
$created_by = 1; // Update later with session admin ID

try {
  $stmt = $conn->prepare("INSERT INTO admin (username, email, role, password, status, date_created, created_by)
                          VALUES (:username, :email, :role, :password, :status, NOW(), :created_by)");
  $stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':role' => $role,
    ':password' => $password,
    ':status' => $status,
    ':created_by' => $created_by
  ]);
  echo json_encode(['success' => true, 'message' => '✅ Admin account successfully created!']);
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
