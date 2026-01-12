<?php
require_once __DIR__ . '/../config/db.php';

function audit_log($table_name, $record_id, $action, $old_data, $new_data, $performed_by)
{
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO audit_log (table_name, record_id, action, old_data, new_data, performed_by, timestamp)
        VALUES (:table_name, :record_id, :action, :old_data, :new_data, :performed_by, NOW())
    ");
    $stmt->execute([
        ':table_name'   => $table_name,
        ':record_id'    => $record_id,
        ':action'       => strtoupper($action),
        ':old_data'     => $old_data !== null ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null,
        ':new_data'     => $new_data !== null ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null,
        ':performed_by' => $performed_by
    ]);
}
