<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Type: text/html; charset=UTF-8");

// Require login
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo '<div class="alert alert-danger">Not authenticated. Please log in again.</div>';
    exit;
}

// Folder that contains all *partial* page files
$pagesDir = __DIR__ . '/pages/';

// Get requested page from URL
$page = isset($_GET['page']) ? basename($_GET['page']) : '';

// Parse hash parameters (reports.php&billing_date=YYYY-MM-DD)
if (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '#')) {
    $hash = parse_url($_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT);
    parse_str(str_replace('reports.php&', '', $hash), $hashParams);

    if (isset($hashParams['billing_date'])) {
        $_POST['billing_date'] = $hashParams['billing_date'];
    }
}

$allowedPages = [
    'trans_entry.php',
    'reports.php',
    'contractor.php',
    'site.php',
    'materials.php',
    'truck.php',
    'company.php',
    'accounts.php',
    'backup.php',
];

if (in_array($page, $allowedPages, true)) {
    // IMPORTANT: files in /pages MUST be partials (no <html>, <body>, etc.)
    include $pagesDir . $page;
} else {
    http_response_code(404);
    echo '<div class="alert alert-danger">Error: Page not found or access denied.</div>';
}

