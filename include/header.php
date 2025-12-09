<header class="app-header navbar navbar-dark bg-warning px-3 d-flex justify-content-between align-items-center">
  <h1 class="app-title m-0 fw-bold text-dark">WELCOME ALPHASAND</h1>
  
  <div class="d-flex align-items-center">
    <?php if(isset($_SESSION['username'])): ?>
      <span class="me-3 text-dark fw-semibold">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <a href="logout.php" class="btn btn-sm btn-outline-dark">Logout</a>
    <?php else: ?>
      <a href="index.php" class="btn btn-sm btn-outline-dark">Login</a>
    <?php endif; ?>
  </div>
</header>
