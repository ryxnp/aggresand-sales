<?php
require_once __DIR__ . '/../include/db.php';

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
