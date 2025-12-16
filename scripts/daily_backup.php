<?php
/**
 * DAILY DATABASE BACKUP SCRIPT
 * SAFE: INSERT-only SQL
 * Run via CRON or Task Scheduler
 */

require_once __DIR__ . '/../config/db.php';

$backupDir = 'C:../Documents/aggresand';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$tables = [
    'admin',
    'company',
    'contractor',
    'customer',
    'delivery',
    'materials',
    'site',
    'statement_of_account',
    'audit_log',
];

$filename = 'backup_' . date('Y-m-d') . '.sql';
$filePath = $backupDir . '/' . $filename;

$fh = fopen($filePath, 'w');

fwrite($fh, "-- Aggresand Daily Backup\n");
fwrite($fh, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");

foreach ($tables as $table) {

    $stmt = $conn->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) continue;

    fwrite($fh, "-- Table: $table\n");

    foreach ($rows as $row) {
        $cols = array_map(fn($c) => "`$c`", array_keys($row));
        $vals = array_map([$conn, 'quote'], array_values($row));

        $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ")
                VALUES (" . implode(',', $vals) . ");\n";

        fwrite($fh, $sql);
    }

    fwrite($fh, "\n");
}

fclose($fh);

/* OPTIONAL: ZIP IT */
$zipPath = $filePath . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);
$zip->addFile($filePath, basename($filePath));
$zip->close();

unlink($filePath); // keep only zip

/* ================= LOG SUCCESS ================= */
$conn->prepare("
    INSERT INTO backup_log (filename)
    VALUES (?)
")->execute([$zipPath]);

echo "Backup created: $zipPath\n";
