<?php
// admin/campaign_tracking_stats.php - Campaign Tracking Statistics (Modern UI)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Campaign Stats';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get admin permissions
$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);

$campaign_id = $_GET['id'] ?? '';

if (empty($campaign_id)) {
    header('Location: manage_campaigns.php');
    exit();
}

try {
    // Get campaign details
    $stmt = $conn->prepare("
        SELECT c.*, GROUP_CONCAT(DISTINCT a.name) as advertiser_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: manage_campaigns.php');
        exit();
    }
    
    // Get publisher tracking statistics
    $stmt = $conn->prepare("
        SELECT 
            p.id as publisher_id, p.name as publisher_name, psc.short_code,
            COALESCE(psc.clicks, 0) as psc_clicks, COALESCE(cp.clicks, 0) as cp_clicks,
            COALESCE(SUM(pdc.clicks), 0) as daily_clicks,
            GREATEST(COALESCE(psc.clicks, 0), COALESCE(cp.clicks, 0), COALESCE(SUM(pdc.clicks), 0)) as clicks
        FROM publishers p
        JOIN campaign_publishers cp ON p.id = cp.publisher_id
        JOIN publisher_short_codes psc ON cp.campaign_id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        LEFT JOIN publisher_daily_clicks pdc ON pdc.campaign_id = cp.campaign_id AND pdc.publisher_id = p.id
        WHERE cp.campaign_id = ?
        GROUP BY p.id, p.name, psc.short_code, psc.clicks, cp.clicks
        ORDER BY p.name
    ");
    $stmt->execute([$campaign_id]);
    $publisher_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_clicks = array_sum(array_column($publisher_stats, 'clicks'));
    
} catch (PDOException $e) {
    $error = "Error loading campaign data: " . $e->getMessage();
}

// Get base URL from environment or use default
$base_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/webnetics-shorturl', '/');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-chart-bar me-2"></i><?php echo htmlspecialchars($campaign['name']); ?></h2>
                    <p class="text-muted mb-0">Campaign tracking statistics</p>
                </div>
                <a href="manage_campaigns.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Campaigns
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Campaign Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Campaign Details
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Base Short Code:</strong> 
                                <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($campaign['shortcode']); ?></code>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?php echo $base_url; ?>/<?php echo htmlspecialchars($campaign['shortcode']); ?>', this)">
                                    <i class="fas fa-copy me-1"></i>Copy Link
                                </button>
                            </p>
                            <p><strong>Advertisers:</strong> <?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></p>
                            <p><strong>Campaign Type:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Target URL:</strong> <a href="<?php echo htmlspecialchars($campaign['target_url']); ?>" target="_blank" class="text-decoration-none"><?php echo htmlspecialchars($campaign['target_url']); ?> <i class="fas fa-external-link-alt ms-1"></i></a></p>
                            <p><strong>Duration:</strong> <?php echo date('M d, Y', strtotime($campaign['start_date'])); ?> - <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($campaign['status']); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($total_clicks); ?></div>
                                <div class="label">Total Clicks</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                                <i class="fas fa-mouse-pointer"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo count($publisher_stats); ?></div>
                                <div class="label">Publishers</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($campaign['conversion_count'] ?? 0); ?></div>
                                <div class="label">Conversions</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Publisher Stats Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-table me-2"></i>Publisher Tracking Statistics</span>
                    <span class="badge bg-primary"><?php echo count($publisher_stats); ?> publishers</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($publisher_stats)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>No publishers assigned to this campaign.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Publisher</th>
                                        <th>Short Code</th>
                                        <th>Tracking Link</th>
                                        <th class="text-center">Clicks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publisher_stats as $stats): 
                                        $tracking_link = $base_url . '/c/' . htmlspecialchars($stats['short_code']) . '/p' . $stats['publisher_id'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon me-2" style="width:35px;height:35px;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#6366f1);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;">
                                                    <?php echo strtoupper(substr($stats['publisher_name'], 0, 1)); ?>
                                                </div>
                                                <span class="fw-medium"><?php echo htmlspecialchars($stats['publisher_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($stats['short_code']); ?></code></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code class="small text-truncate" style="max-width: 300px;"><?php echo $tracking_link; ?></code>
                                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?php echo $tracking_link; ?>', this)">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?php echo number_format($stats['clicks']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-light">
                                        <td colspan="3" class="fw-bold">Total Clicks</td>
                                        <td class="text-center fw-bold text-success"><?php echo number_format($total_clicks); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
