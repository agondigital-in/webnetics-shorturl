<?php
// super_admin/dashboard.php - Super Admin Dashboard
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Dashboard';
$include_chartjs = true;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get counts
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM campaigns");
    $stmt->execute();
    $campaigns_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM advertisers");
    $stmt->execute();
    $advertisers_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM publishers");
    $stmt->execute();
    $publishers_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
    $stmt->execute();
    $admins_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(click_count), 0) as total FROM campaigns");
    $stmt->execute();
    $total_clicks = $stmt->fetch()['total'];
    
    // Recent campaigns
    $stmt = $conn->prepare("SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Campaign status
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM campaigns GROUP BY status");
    $stmt->execute();
    $campaign_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Campaign types
    $stmt = $conn->prepare("SELECT campaign_type, COUNT(*) as count FROM campaigns GROUP BY campaign_type");
    $stmt->execute();
    $campaign_type_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
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
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($campaigns_count); ?></div>
                                <div class="stat-label">Campaigns</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($advertisers_count); ?></div>
                                <div class="stat-label">Advertisers</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($publishers_count); ?></div>
                                <div class="stat-label">Publishers</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-share-alt"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
                                <div class="stat-label">Total Clicks</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-mouse-pointer"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">Campaign Status</div>
                        <div class="card-body">
                            <canvas id="statusChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">Campaign Types</div>
                        <div class="card-body">
                            <canvas id="typeChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Campaigns -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent Campaigns</span>
                    <a href="campaigns.php" class="btn btn-sm btn-primary-custom">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_campaigns)): ?>
                        <div class="p-4 text-center text-muted">No campaigns yet</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Short Code</th>
                                        <th>Type</th>
                                        <th>Clicks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_campaigns as $campaign): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($campaign['name']); ?></td>
                                        <td><code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                        <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                        <td class="fw-bold text-primary"><?php echo number_format($campaign['click_count']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = "
    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: " . json_encode(array_column($campaign_status_data, 'status')) . ",
            datasets: [{
                data: " . json_encode(array_column($campaign_status_data, 'count')) . ",
                backgroundColor: ['#10b981', '#6b7280']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    
    // Type Chart
    new Chart(document.getElementById('typeChart'), {
        type: 'pie',
        data: {
            labels: " . json_encode(array_column($campaign_type_data, 'campaign_type')) . ",
            datasets: [{
                data: " . json_encode(array_column($campaign_type_data, 'count')) . ",
                backgroundColor: ['#4f46e5', '#06b6d4', '#f59e0b', '#10b981', '#ec4899', '#8b5cf6']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
";
require_once 'includes/footer.php';
?>