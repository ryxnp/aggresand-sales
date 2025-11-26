<?php
// include/db.php

// Database configuration
$host = "localhost";        // or your database host
$username = "root";         // your MySQL username
$password = "9320718a";             // your MySQL password
$database = "aggresand_db"; // your database name

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);

    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: ensure timezone is set correctly for timestamps
    date_default_timezone_set('Asia/Manila');
    
} catch (PDOException $e) {
    // If connection fails, stop execution and display error
    die("Database connection failed: " . $e->getMessage());
}
?>
