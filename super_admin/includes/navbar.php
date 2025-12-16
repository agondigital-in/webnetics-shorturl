<?php
// super_admin/includes/navbar.php - Common navbar for all super_admin pages
?>
<nav class="navbar main-navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-chart-line me-2"></i>webnetics-shorturl Ads
        </a>
        <button class="navbar-toggler mobile-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <i class="fas fa-bars text-white"></i>
        </button>
        <div class="d-flex align-items-center">
            <span class="text-white me-3 d-none d-md-inline">
                <i class="fas fa-user-circle me-1"></i>
                <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
            </span>
            <a class="btn btn-logout" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i><span class="d-none d-sm-inline">Logout</span>
            </a>
        </div>
    </div>
</nav>
