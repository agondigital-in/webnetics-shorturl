<?php
// super_admin/includes/sidebar.php - Common sidebar for all super_admin pages
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Desktop Sidebar -->
<div class="col-lg-2 d-none d-lg-block sidebar p-0">
    <ul class="nav flex-column pt-2">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                <i class="fas fa-bullhorn"></i> Campaigns
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                <i class="fas fa-users"></i> Advertisers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                <i class="fas fa-share-alt"></i> Publishers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                <i class="fas fa-user-shield"></i> Admins
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($current_page, ['advertiser_campaigns.php']) ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                <i class="fas fa-ad"></i> Advertiser Campaigns
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($current_page, ['publisher_campaigns.php']) ? 'active' : ''; ?>" href="publisher_campaigns.php">
                <i class="fas fa-link"></i> Publisher Campaigns
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($current_page, ['all_publishers_daily_clicks.php', 'publisher_daily_clicks.php', 'campaign_tracking_stats.php']) ? 'active' : ''; ?>" href="all_publishers_daily_clicks.php">
                <i class="fas fa-chart-bar"></i> Publishers Stats
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($current_page, ['payment_reports.php', 'daily_report.php']) ? 'active' : ''; ?>" href="payment_reports.php">
                <i class="fas fa-file-invoice-dollar"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'security_settings.php' ? 'active' : ''; ?>" href="security_settings.php">
                <i class="fas fa-lock"></i> Security
            </a>
        </li>
    </ul>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
        <h5 class="offcanvas-title text-white"><i class="fas fa-chart-line me-2"></i>webnetics-shorturl</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                    <i class="fas fa-bullhorn me-2"></i> Campaigns
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                    <i class="fas fa-users me-2"></i> Advertisers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                    <i class="fas fa-share-alt me-2"></i> Publishers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                    <i class="fas fa-user-shield me-2"></i> Admins
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'security_settings.php' ? 'active' : ''; ?>" href="security_settings.php">
                    <i class="fas fa-lock me-2"></i> Security
                </a>
            </li>
        </ul>
    </div>
</div>
