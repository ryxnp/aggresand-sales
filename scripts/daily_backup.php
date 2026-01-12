<?php
/**
 * DAILY DATABASE BACKUP
 * - Excel (multi-sheet)
 * - SQL (insert-only, zipped)
 * - CSV (all tables, zipped)
 * Safe for Task Scheduler / Cron
 */

require_once __DIR__ . '/../config/db.php';

/* ---------------- CONFIG ---------------- */

//Developer's backup directory
// $BASE_DIR = 'C:\Users\Public\Documents\aggresand';
    
//Client's backup directory
$BASE_DIR = 'C:\Users\pc\Documents\alphasand_backup';

$DIRS = [
    'excel' => "$BASE_DIR/excel",
    'sql'   => "$BASE_DIR/sql",
    'csv'   => "$BASE_DIR/csv",
];

foreach ($DIRS as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

$tables = [
    'admin',
    'company',
    'contractor',
    'customer',
    'delivery',
    'materials',
    'site',
    'truck',
    'statement_of_account',
    'audit_log',
    'backup_log',
];

$ts = date('Y-m-d_His');
$PREFIX = 'AUTO_';

/* =====================================================
   1️⃣ EXCEL BACKUP (XML XLS – MULTI SHEET)
===================================================== */

$excelFile = "{$DIRS['excel']}/{$PREFIX}backup_$ts.xls";

$xml  = '<?xml version="1.0"?>' . "\n";
$xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

foreach ($tables as $table) {
    $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) continue;

    $xml .= '<Worksheet ss:Name="' . htmlspecialchars($table) . '"><Table>';

    // header
    $xml .= '<Row>';
    foreach (array_keys($rows[0]) as $col) {
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($col) . '</Data></Cell>';
    }
    $xml .= '</Row>';

    // rows
    foreach ($rows as $row) {
        $xml .= '<Row>';
        foreach ($row as $val) {
            $type = is_numeric($val) ? 'Number' : 'String';
            $xml .= '<Cell><Data ss:Type="' . $type . '">' .
                    htmlspecialchars((string)$val) .
                    '</Data></Cell>';
        }
        $xml .= '</Row>';
    }

    $xml .= '</Table></Worksheet>';
}

$xml .= '</Workbook>';

file_put_contents($excelFile, $xml);

/* =====================================================
   2️⃣ SQL BACKUP (INSERT-ONLY → ZIP)
===================================================== */

$sqlFile = "{$DIRS['sql']}/{$PREFIX}backup_$ts.sql";
$fh = fopen($sqlFile, 'w');

fwrite($fh, "-- Aggresand SQL Backup\n-- $ts\n\n");

foreach ($tables as $table) {
    $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) continue;

    fwrite($fh, "-- TABLE: $table\n");

    foreach ($rows as $row) {
        $cols = array_map(fn($c) => "`$c`", array_keys($row));
        $vals = array_map([$conn, 'quote'], array_values($row));
        fwrite(
            $fh,
            "INSERT INTO `$table` (" . implode(',', $cols) . ")
             VALUES (" . implode(',', $vals) . ");\n"
        );
    }
    fwrite($fh, "\n");
}
fclose($fh);

// zip SQL
$sqlZip = "{$sqlFile}.zip";
$zip = new ZipArchive();
$zip->open($sqlZip, ZipArchive::CREATE);
$zip->addFile($sqlFile, basename($sqlFile));
$zip->close();
unlink($sqlFile);

/* =====================================================
   3️⃣ CSV BACKUP (ALL TABLES → ZIP)
===================================================== */

$csvZip = "{$DIRS['csv']}/{$PREFIX}backup_$ts.csv.zip";
$zip = new ZipArchive();
$zip->open($csvZip, ZipArchive::CREATE);

foreach ($tables as $table) {
    $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) continue;

    $fp = fopen('php://temp', 'w+');
    fputcsv($fp, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($fp, $row);
    rewind($fp);

    $zip->addFromString("$table.csv", stream_get_contents($fp));
    fclose($fp);
}
$zip->close();

/* =====================================================
   4️⃣ LOG BACKUPS
===================================================== */

$log = $conn->prepare("
    INSERT INTO backup_log (filename, type, created_by)
    VALUES (?, ?, NULL)
");

$log->execute([basename($excelFile), 'excel']);
$log->execute([basename($sqlZip),   'sql']);
$log->execute([basename($csvZip),   'csv']);

echo "Daily backup completed: $ts\n";
