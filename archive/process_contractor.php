<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$current_user = $_SESSION['user_id'];

// CREATE CONTRACTOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_contractor'])) {
    $name    = trim($_POST['contractor_name']);
    $person  = trim($_POST['contact_person']);
    $contact = trim($_POST['contact_no']);
    $email   = trim($_POST['email']);
    $status  = trim($_POST['status']);

    if (!$name || !$person || !$contact || !$email || !$status) {
        $_SESSION['error'] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $_SESSION['error'] = "Invalid status.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO contractor
                (contractor_name, contact_person, contact_no, email, status, is_deleted, date_created, created_by)
                VALUES (:name, :person, :contact, :email, :status, 0, NOW(), :created_by)");
            $stmt->execute([
                ':name'       => $name,
                ':person'     => $person,
                ':contact'    => $contact,
                ':email'      => $email,
                ':status'     => $status,
                ':created_by' => $current_user
            ]);
            $_SESSION['success'] = "Contractor created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating contractor: " . $e->getMessage();
        }
    }
    header("Location: ../pages/contractor.php");
    exit;
}

// UPDATE CONTRACTOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contractor'])) {
    $id      = (int)$_POST['contractor_id'];
    $name    = trim($_POST['contractor_name']);
    $person  = trim($_POST['contact_person']);
    $contact = trim($_POST['contact_no']);
    $email   = trim($_POST['email']);
    $status  = trim($_POST['status']);

    if (!$id || !$name || !$person || !$contact || !$email || !$status) {
        $_SESSION['error'] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $_SESSION['error'] = "Invalid status.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE contractor
                SET contractor_name = :name,
                    contact_person = :person,
                    contact_no = :contact,
                    email = :email,
                    status = :status,
                    date_edited = NOW(),
                    edited_by = :edited_by
                WHERE contractor_id = :id AND is_deleted = 0");
            $stmt->execute([
                ':name'      => $name,
                ':person'    => $person,
                ':contact'   => $contact,
                ':email'     => $email,
                ':status'    => $status,
                ':edited_by' => $current_user,
                ':id'        => $id
            ]);
            $_SESSION['success'] = "Contractor updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating contractor: " . $e->getMessage();
        }
    }
    header("Location: ../pages/contractor.php");
    exit;
}

// SOFT DELETE CONTRACTOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contractor'])) {
    $id = (int)$_POST['contractor_id'];
    if ($id) {
        try {
            $stmt = $conn->prepare("UPDATE contractor SET is_deleted = 1 WHERE contractor_id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = "Contractor deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting contractor: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid contractor ID.";
    }
    header("Location: ../pages/contractor.php");
    exit;
}
