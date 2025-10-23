<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../include/db.php';

$stmt = $conn->query("SELECT 1 AS test");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3 style='color:green;'>✅ Database connected successfully!</h3>";
echo "Test result: <strong>" . $row['test'] . "</strong>";

?>
