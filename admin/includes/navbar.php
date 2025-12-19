<?php
// admin/includes/navbar.php - Common navbar for admin pages
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-4">
        <button class="btn btn-link text-white d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-chart-line me-2"></i>Admin Panel</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text text-white me-3">
                <i class="fas fa-user me-1"></i>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a class="btn btn-outline-light btn-sm" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>
