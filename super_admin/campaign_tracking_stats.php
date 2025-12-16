<?php
// super_admin/campaign_tracking_stats.php - Campaign Tracking Statistics
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Campaign Statistics';

// Detect environment and set base URL
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false);
$base_url = $is_localhost ? 'http://' . $_SERVER['HTTP_HOST'] . '/webnetics-shorturl/c/' : 'https://tracking.webneticads.com/c/';

$campaign_id = $_GET['id'] ?? '';

if (empty($campaign_id)) {
    header('Location: campaigns.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
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
        header('Location: campaigns.php');
        exit();
    }
    
    $stmt = $conn->prepare("
        SELECT p.name as publisher_name, psc.short_code, COALESCE(psc.clicks, 0) as clicks
        FROM publishers p
        JOIN campaign_publishers cp ON p.id = cp.publisher_id
        JOIN publisher_short_codes psc ON cp.campaign_id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        WHERE cp.campaign_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$campaign_id]);
    $publisher_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading campaign data: " . $e->getMessage();
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="page-header mb-0">
                    <h2><i class="fas fa-chart-bar me-2"></i>Campaign Statistics</h2>
                    <p><?php echo htmlspecialchars($campaign['name']); ?></p>
                </div>
                <a href="campaigns.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Campaign Details -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Campaign Details</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Short Code:</strong> <code><?php echo htmlspecialchars($campaign['shortcode']); ?></code>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?php echo rtrim($base_url, '/c/') . '/' . htmlspecialchars($campaign['shortcode']); ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </p>
                            <p><strong>Advertisers:</strong> <?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></p>
                            <p><strong>Start Date:</strong> <?php echo htmlspecialchars($campaign['start_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Type:</strong> <span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></p>
                            <p><strong>Target URL:</strong> <a href="<?php echo htmlspecialchars($campaign['target_url']); ?>" target="_blank"><?php echo htmlspecialchars($campaign['target_url']); ?></a></p>
                            <p><strong>End Date:</strong> <?php echo htmlspecialchars($campaign['end_date']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Publisher Stats -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Publisher Tracking Statistics</span>
                    <a href="publisher_daily_clicks.php?id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-info text-white">
                        <i class="fas fa-calendar-alt me-1"></i>Daily Clicks
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($publisher_stats)): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No publishers assigned</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Publisher</th>
                                        <th>Short Code</th>
                                        <th>Tracking Link</th>
                                        <th class="text-end">Clicks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_clicks = 0;
                                    foreach ($publisher_stats as $stats): 
                                        $total_clicks += $stats['clicks'];
                                    ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($stats['publisher_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($stats['short_code']); ?></code></td>
                                            <td>
                                                <code class="small"><?php echo $base_url . htmlspecialchars($stats['short_code']); ?></code>
                                                <button class="btn btn-sm btn-link p-0 ms-2" onclick="copyToClipboard('<?php echo $base_url . htmlspecialchars($stats['short_code']); ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                            <td class="text-end fw-bold"><?php echo number_format($stats['clicks']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-light">
                                        <td colspan="3" class="fw-bold text-end">Total Clicks</td>
                                        <td class="fw-bold text-end text-primary"><?php echo number_format($total_clicks); ?></td>
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

<?php
$extra_js = "
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied!');
        });
    }
";
require_once 'includes/footer.php';
?>