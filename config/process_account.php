<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $role       = trim($_POST['role']);
    $password   = trim($_POST['password']);
    $created_by = $_SESSION['user_id'];

    if ($username && $email && $role && $password) {
        try {
            $stmt = $conn->prepare("INSERT INTO user_account 
                (username,email,role,password,status,created_by,date_created)
                VALUES(:username,:email,:role,:password,'active',:created_by,NOW())");
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

    header("Location: ../pages/accounts.php");
    exit;
}
