<?php
// super_admin/advertiser_campaigns.php - View Advertiser Campaigns
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Advertiser Campaigns';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            a.id as advertiser_id, a.name as advertiser_name, a.email as advertiser_email,
            c.id as campaign_id, c.name as campaign_name, c.shortcode,
            c.start_date, c.end_date, c.campaign_type, c.click_count, c.status
        FROM advertisers a
        LEFT JOIN campaign_advertisers ca ON a.id = ca.advertiser_id
        LEFT JOIN campaigns c ON ca.campaign_id = c.id
        ORDER BY a.name, c.name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by advertiser
    $advertiser_campaigns = [];
    foreach ($results as $row) {
        $advertiser_id = $row['advertiser_id'];
        if (!isset($advertiser_campaigns[$advertiser_id])) {
            $advertiser_campaigns[$advertiser_id] = [
                'name' => $row['advertiser_name'],
                'email' => $row['advertiser_email'],
                'campaigns' => []
            ];
        }
        if ($row['campaign_id']) {
            $advertiser_campaigns[$advertiser_id]['campaigns'][] = $row;
        }
    }
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-ad me-2"></i>Advertiser Campaigns</h2>
                <p>View campaigns assigned to each advertiser</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (empty($advertiser_campaigns)): ?>
                <div class="card"><div class="card-body text-center p-5 text-muted">No advertisers found.</div></div>
            <?php else: ?>
                <?php foreach ($advertiser_campaigns as $advertiser): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-1"><?php echo htmlspecialchars($advertiser['name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($advertiser['email']); ?></small>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($advertiser['campaigns'])): ?>
                                <p class="p-3 text-muted mb-0">No campaigns assigned.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Campaign</th>
                                                <th>Short Code</th>
                                                <th>Type</th>
                                                <th>Dates</th>
                                                <th>Clicks</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($advertiser['campaigns'] as $campaign): ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                                <td><code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                                <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                                <td><?php echo $campaign['start_date']; ?> to <?php echo $campaign['end_date']; ?></td>
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
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>