<?php
// admin/publisher_campaigns.php - View Publisher Campaigns (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Publisher Campaigns';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('publisher_campaigns_view', $admin_permissions)) {
    header('Location: dashboard.php');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            p.id as publisher_id, p.name as publisher_name, p.email as publisher_email, p.website,
            c.id as campaign_id, c.name as campaign_name, c.shortcode as base_shortcode,
            c.start_date, c.end_date, c.campaign_type, c.click_count as total_clicks, c.status,
            psc.short_code as publisher_shortcode, COALESCE(psc.clicks, 0) as publisher_clicks
        FROM publishers p
        LEFT JOIN campaign_publishers cp ON p.id = cp.publisher_id
        LEFT JOIN campaigns c ON cp.campaign_id = c.id
        LEFT JOIN publisher_short_codes psc ON c.id = psc.campaign_id AND p.id = psc.publisher_id
        ORDER BY p.name, c.name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $publisher_campaigns = [];
    $total_publishers = 0;
    $total_assignments = 0;
    $total_clicks = 0;
    
    foreach ($results as $row) {
        $publisher_id = $row['publisher_id'];
        if (!isset($publisher_campaigns[$publisher_id])) {
            $publisher_campaigns[$publisher_id] = [
                'name' => $row['publisher_name'],
                'email' => $row['publisher_email'],
                'website' => $row['website'],
                'campaigns' => []
            ];
            $total_publishers++;
        }
        if ($row['campaign_id']) {
            $publisher_campaigns[$publisher_id]['campaigns'][] = $row;
            $total_assignments++;
            $total_clicks += $row['publisher_clicks'];
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
                <h2 class="mb-1"><i class="fas fa-link me-2"></i>Publisher Campaigns</h2>
                <p class="text-muted mb-0">View campaigns and tracking links for each publisher</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $total_publishers; ?></div>
                                <div class="label">Publishers</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $total_assignments; ?></div>
                                <div class="label">Assignments</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-bullhorn"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($total_clicks); ?></div>
                                <div class="label">Total Clicks</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-mouse-pointer"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($publisher_campaigns)): ?>
                <div class="card"><div class="card-body text-center p-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><p>No publishers found.</p></div></div>
            <?php else: ?>
                <?php foreach ($publisher_campaigns as $publisher): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div class="icon me-3" style="width:45px;height:45px;border-radius:10px;background:linear-gradient(135deg,#4f46e5,#6366f1);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;">
                                    <?php echo strtoupper(substr($publisher['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($publisher['name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($publisher['email']); ?></small>
                                    <?php if ($publisher['website']): ?>
                                        <br><small><a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank"><?php echo htmlspecialchars($publisher['website']); ?> <i class="fas fa-external-link-alt"></i></a></small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-primary ms-auto"><?php echo count($publisher['campaigns']); ?> campaigns</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($publisher['campaigns'])): ?>
                                <p class="p-3 text-muted mb-0">No campaigns assigned.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Campaign</th>
                                                <th>Publisher Short Code</th>
                                                <th>Type</th>
                                                <th class="text-center">Publisher Clicks</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($publisher['campaigns'] as $campaign): ?>
                                            <tr>
                                                <td class="fw-medium"><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($campaign['publisher_shortcode'] ?? 'N/A'); ?></code></td>
                                                <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                                <td class="text-center fw-bold text-primary"><?php echo number_format($campaign['publisher_clicks']); ?></td>
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
