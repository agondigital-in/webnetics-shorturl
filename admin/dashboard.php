<?php
// admin/dashboard.php - Admin Dashboard (Modern UI)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Dashboard';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get admin permissions
$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);

try {
    // Get counts
    $campaigns_count = $conn->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
    $active_campaigns = $conn->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active'")->fetchColumn();
    $advertisers_count = $conn->query("SELECT COUNT(*) FROM advertisers")->fetchColumn();
    $publishers_count = $conn->query("SELECT COUNT(*) FROM publishers")->fetchColumn();
    $total_clicks = $conn->query("SELECT COALESCE(SUM(click_count), 0) FROM campaigns")->fetchColumn();
    $total_conversions = $conn->query("SELECT COALESCE(SUM(conversion_count), 0) FROM campaigns")->fetchColumn();
    
    // Recent campaigns
    $stmt = $conn->prepare("SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent publishers
    $stmt = $conn->prepare("SELECT * FROM publishers ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $recent_publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading dashboard: " . $e->getMessage();
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-home me-2"></i>Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $campaigns_count; ?></div>
                                <div class="label">Total Campaigns</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $active_campaigns; ?></div>
                                <div class="label">Active Campaigns</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $publishers_count; ?></div>
                                <div class="label">Publishers</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $advertisers_count; ?></div>
                                <div class="label">Advertisers</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                                <i class="fas fa-ad"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Stats -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($total_clicks); ?></div>
                                <div class="label">Total Clicks</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                <i class="fas fa-mouse-pointer"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($total_conversions); ?></div>
                                <div class="label">Total Conversions</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Campaigns -->
                <div class="col-lg-7 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-bullhorn me-2"></i>Recent Campaigns</span>
                            <?php $is_super_admin = ($_SESSION['role'] === 'super_admin'); ?>
                            <?php if ($is_super_admin || in_array('campaigns_view', $admin_permissions)): ?>
                            <a href="manage_campaigns.php" class="btn btn-sm btn-primary">View All</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_campaigns)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">No campaigns yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Shortcode</th>
                                                <th>Status</th>
                                                <th>Clicks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_campaigns as $campaign): ?>
                                            <tr>
                                                <td class="fw-medium"><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($campaign['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-bold text-primary"><?php echo number_format($campaign['click_count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Publishers -->
                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-users me-2"></i>Recent Publishers</span>
                            <?php if ($is_super_admin || in_array('publishers_view', $admin_permissions)): ?>
                            <a href="manage_publishers.php" class="btn btn-sm btn-primary">View All</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_publishers)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <p class="mb-0">No publishers yet</p>
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recent_publishers as $publisher): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-circle text-primary me-2"></i>
                                            <span class="fw-medium"><?php echo htmlspecialchars($publisher['name']); ?></span>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($publisher['email']); ?></small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">ID: <?php echo $publisher['id']; ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
