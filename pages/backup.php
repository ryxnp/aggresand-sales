<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$adminId = $_SESSION['admin_id'];

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

//Developer's backup directory
$BASE_BACKUP_DIR = 'C:\Users\Public\Documents\aggresand';

//Client's backup directory
//$BASE_BACKUP_DIR = 'C:\Users\pc\Documents\alphasand_backup';

/* ================= SQL ZIP BACKUP ================= */
if (isset($_GET['action']) && $_GET['action'] === 'backup_zip') {

    $dir = $BASE_BACKUP_DIR . '/sql/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $filename = 'MANUAL_sql_' . date('Ymd_His') . '.zip';
    $path = $dir . $filename;

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($tables as $table) {
        $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) continue;

        $sql = '';
        foreach ($rows as $row) {
            $cols = array_map(fn($c) => "`$c`", array_keys($row));
            $vals = array_map([$conn, 'quote'], array_values($row));
            $sql .= "INSERT INTO `$table` (" . implode(',', $cols) . ")
                     VALUES (" . implode(',', $vals) . ");\n";
        }
        $zip->addFromString("$table.sql", $sql);
    }

    $zip->close();

    $conn->prepare("
        INSERT INTO backup_log (filename, type, created_by)
        VALUES (?, 'sql', ?)
    ")->execute([$filename, $adminId]);

    $_SESSION['alert'] = [
        'type'    => 'success',
        'message' => "SQL backup completed successfully.<br><small>Saved to: $path</small>"
    ];

    header("Location: /main.php#backup.php");
    exit;
}

/* ================= CSV BACKUP ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backup_csv') {

    $dir = $BASE_BACKUP_DIR . '/csv/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $selected = $_POST['tables'] ?? $tables;

    $filename = 'MANUAL_csv_' . date('Ymd_His') . '.zip';
    $path = $dir . $filename;

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($selected as $table) {
        if (!in_array($table, $tables)) continue;

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

    $conn->prepare("
        INSERT INTO backup_log (filename, type, created_by)
        VALUES (?, 'csv', ?)
    ")->execute([$filename, $adminId]);

    $_SESSION['alert'] = [
        'type'    => 'success',
        'message' => "CSV backup completed successfully.<br><small>Saved to: $path</small>"
    ];

    header("Location: /main.php#backup.php");
    exit;
}

/* ================= CSV IMPORT (INSERT-ONLY) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_csv') {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        die('Invalid CSV upload');
    }

    $table = $_POST['table'] ?? '';
    if (!in_array($table, $tables)) {
        die('Invalid table');
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$handle) die('Cannot read CSV');

    $columns = fgetcsv($handle);
    if (!$columns) die('Missing CSV header');

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);

    $conn->beginTransaction();
    try {
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($columns)) continue;
            $stmt->execute($row);
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        fclose($handle);
        die('Import failed: ' . $e->getMessage());
    }

    fclose($handle);
    header("Location: backup.php");
    exit;
}

/* ================= BACKUP HISTORY ================= */
$history = $conn->query("
    SELECT b.id, b.filename, b.type, b.created_at, a.username
    FROM backup_log b
    LEFT JOIN admin a ON a.admin_id = b.created_by
    ORDER BY b.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Database Backup & Restore</h2>

    <?php if (!empty($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['alert']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <!-- BACKUP ALL -->
    <div class="card mb-4">
        <div class="card-body">
            <a href="pages/backup.php?action=backup_zip" class="btn btn-primary">
                📦 Backup All (SQL ZIP)
            </a>
        </div>
    </div>

    <div class="row">

        <!-- CSV IMPORT -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">CSV Import (INSERT-only)</div>
                <div class="card-body">
                    <form method="POST"
                          action="pages/backup.php"
                          enctype="multipart/form-data">

                        <input type="hidden" name="action" value="import_csv">

                        <div class="mb-3">
                            <label class="form-label">Table</label>
                            <select name="table" class="form-select" required>
                                <?php foreach ($tables as $t): ?>
                                    <option value="<?= $t ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>

                        <button class="btn btn-warning">⬆ Import CSV</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CSV BACKUP -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">CSV Backup</div>
                <div class="card-body">
                    <form method="POST" action="pages/backup.php">
                        <input type="hidden" name="action" value="backup_csv">

                        <?php foreach ($tables as $t): ?>
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="tables[]"
                                       value="<?= $t ?>"
                                       checked>
                                <label class="form-check-label"><?= $t ?></label>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn btn-success mt-3">
                            📄 Backup Selected as CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- HISTORY -->
    <div class="card">
        <div class="card-header">Backup History</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Created By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$history): ?>
                    <tr><td colspan="5" class="text-center">No backups found</td></tr>
                <?php else: foreach ($history as $h): ?>
                    <tr>
                        <td><?= $h['id'] ?></td>
                        <td><?= htmlspecialchars($h['filename']) ?></td>
                        <td><?= strtoupper($h['type']) ?></td>
                        <td><?= htmlspecialchars($h['username'] ?? 'System') ?></td>
                        <td><?= $h['created_at'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
