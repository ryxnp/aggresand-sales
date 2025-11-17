<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Folder that contains all page files
$pagesDir = __DIR__ . '/pages/';

// Get requested page from URL
$page = isset($_GET['page']) ? basename($_GET['page']) : '';

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

if (in_array($page, $allowedPages)) {
  include $pagesDir . $page;
} else {
  http_response_code(404);
  echo '<p style="color:red;">Error: Page not found or access denied.</p>';
}
?>
