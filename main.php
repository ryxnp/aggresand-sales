<?php 
session_start(); // Start the session at the top

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php"); // Redirect to login if not logged in
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Main Dashboard</title>

  <!-- Bootstrap (optional) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>

  <?php include('include/header.php'); ?>

  <div class="app-container d-flex">
    <?php include('include/sidebar.php'); ?>

    <main class="app-content flex-grow-1 p-4 position-relative">
      <!-- Spinner overlay (always present) -->
      <div class="loading-spinner" id="loading-spinner" style="display:none;">
        <div class="spinner"></div>
        <p>Loading...</p>
      </div>

      <!-- Main dynamic content -->
      <div id="main-content" class="content-area">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Select a module from the sidebar.</p>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script src="js/company.js"></script>
  <script src="js/deliveries.js"></script>
  <script src="js/contractor.js"></script>
  <script src="js/site.js"></script>
  <script src="js/soa.js"></script>
  <script src="js/truck.js"></script>
  <script src="js/trans_entry.js"></script>
  <script src="js/materials.js"></script>
  <script src="js/reports.js"></script>
  <script src="js/scripts.js"></script>
  </body>
</html>
