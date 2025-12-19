<?php
// admin/includes/sidebar.php - Permission-based sidebar for admin pages
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin permissions if not already loaded
if (!isset($admin_permissions)) {
    require_once __DIR__ . '/check_permission.php';
    $admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
}

// Check if user is super_admin (has all permissions)
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Helper function to check permission
function canAccess($permission, $admin_permissions, $is_super_admin) {
    if ($is_super_admin) return true;
    return in_array($permission, $admin_permissions);
}
?>
<!-- Desktop Sidebar -->
<div class="col-lg-2 d-none d-lg-block sidebar p-0">
    <ul class="nav flex-column pt-2">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <?php if (canAccess('campaigns_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_campaigns.php' ? 'active' : ''; ?>" href="manage_campaigns.php">
                <i class="fas fa-bullhorn"></i> Campaigns
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('advertisers_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_advertisers.php' ? 'active' : ''; ?>" href="manage_advertisers.php">
                <i class="fas fa-users"></i> Advertisers
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('publishers_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_publishers.php' ? 'active' : ''; ?>" href="manage_publishers.php">
                <i class="fas fa-share-alt"></i> Publishers
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('admins_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'manage_admins.php' ? 'active' : ''; ?>" href="manage_admins.php">
                <i class="fas fa-user-shield"></i> Admins
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('advertiser_campaigns_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                <i class="fas fa-ad"></i> Advertiser Campaigns
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('publisher_campaigns_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                <i class="fas fa-link"></i> Publisher Campaigns
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('publishers_stats_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($current_page, ['publishers_stats.php', 'campaign_tracking_stats.php']) ? 'active' : ''; ?>" href="publishers_stats.php">
                <i class="fas fa-chart-bar"></i> Publishers Stats
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('reports_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                <i class="fas fa-file-invoice-dollar"></i> Reports
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('security_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'security_settings.php' ? 'active' : ''; ?>" href="security_settings.php">
                <i class="fas fa-lock"></i> Security
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('db_backup_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'database_backup.php' ? 'active' : ''; ?>" href="database_backup.php">
                <i class="fas fa-database"></i> DB Backup
            </a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('admin_permissions_view', $admin_permissions, $is_super_admin)): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'admin_permissions.php' ? 'active' : ''; ?>" href="admin_permissions.php">
                <i class="fas fa-user-cog"></i> Admin Permissions
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
        <h5 class="offcanvas-title text-white"><i class="fas fa-chart-line me-2"></i>Admin Panel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <?php if (canAccess('campaigns_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'manage_campaigns.php' ? 'active' : ''; ?>" href="manage_campaigns.php">
                    <i class="fas fa-bullhorn me-2"></i> Campaigns
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('advertisers_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'manage_advertisers.php' ? 'active' : ''; ?>" href="manage_advertisers.php">
                    <i class="fas fa-users me-2"></i> Advertisers
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('publishers_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'manage_publishers.php' ? 'active' : ''; ?>" href="manage_publishers.php">
                    <i class="fas fa-share-alt me-2"></i> Publishers
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('admins_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'manage_admins.php' ? 'active' : ''; ?>" href="manage_admins.php">
                    <i class="fas fa-user-shield me-2"></i> Admins
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('advertiser_campaigns_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                    <i class="fas fa-ad me-2"></i> Advertiser Campaigns
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('publisher_campaigns_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                    <i class="fas fa-link me-2"></i> Publisher Campaigns
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('publishers_stats_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['publishers_stats.php', 'campaign_tracking_stats.php']) ? 'active' : ''; ?>" href="publishers_stats.php">
                    <i class="fas fa-chart-bar me-2"></i> Publishers Stats
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('reports_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Reports
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('security_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'security_settings.php' ? 'active' : ''; ?>" href="security_settings.php">
                    <i class="fas fa-lock me-2"></i> Security
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('db_backup_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'database_backup.php' ? 'active' : ''; ?>" href="database_backup.php">
                    <i class="fas fa-database me-2"></i> DB Backup
                </a>
            </li>
            <?php endif; ?>
            <?php if (canAccess('admin_permissions_view', $admin_permissions, $is_super_admin)): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'admin_permissions.php' ? 'active' : ''; ?>" href="admin_permissions.php">
                    <i class="fas fa-user-cog me-2"></i> Admin Permissions
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
