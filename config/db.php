<?php

// Developer's Database configuration
$host = "localhost";
$username = "root";
$password = "9320718a";
$database = "aggresand_db";

//Client's Database configuration
// $host = "localhost";
// $username = "root";
// $password = "AdminPasword123!";
// $database = "aggresand_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    date_default_timezone_set('Asia/Manila');
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
