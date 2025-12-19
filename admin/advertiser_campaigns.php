<?php
// admin/advertiser_campaigns.php - View Advertiser Campaigns (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Advertiser Campaigns';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('advertiser_campaigns_view', $admin_permissions)) {
    header('Location: dashboard.php');
    exit();
}

try {
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
            <div class="page-header mb-4">
                <h2 class="mb-1"><i class="fas fa-ad me-2"></i>Advertiser Campaigns</h2>
                <p class="text-muted mb-0">View campaigns assigned to each advertiser</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (empty($advertiser_campaigns)): ?>
                <div class="card"><div class="card-body text-center p-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><p>No advertisers found.</p></div></div>
            <?php else: ?>
                <?php foreach ($advertiser_campaigns as $advertiser): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div class="icon me-3" style="width:45px;height:45px;border-radius:10px;background:linear-gradient(135deg,#06b6d4,#0891b2);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;">
                                    <?php echo strtoupper(substr($advertiser['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($advertiser['name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($advertiser['email']); ?></small>
                                </div>
                                <span class="badge bg-primary ms-auto"><?php echo count($advertiser['campaigns']); ?> campaigns</span>
                            </div>
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
                                                <th>Duration</th>
                                                <th class="text-center">Clicks</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($advertiser['campaigns'] as $campaign): ?>
                                            <tr>
                                                <td class="fw-medium"><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                                <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                                <td><small><?php echo date('M d', strtotime($campaign['start_date'])); ?> - <?php echo date('M d', strtotime($campaign['end_date'])); ?></small></td>
                                                <td class="text-center fw-bold text-primary"><?php echo number_format($campaign['click_count']); ?></td>
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
