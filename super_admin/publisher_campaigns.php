<?php
// super_admin/publisher_campaigns.php - View Publisher Campaigns
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Publisher Campaigns';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
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
    
    // Group by publisher
    $publisher_campaigns = [];
    $total_publishers = 0;
    $total_campaigns = 0;
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
            $total_campaigns++;
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
            <div class="page-header">
                <h2><i class="fas fa-link me-2"></i>Publisher Campaigns</h2>
                <p>View campaigns and tracking links for each publisher</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo $total_publishers; ?></div>
                                <div class="stat-label">Publishers</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo $total_campaigns; ?></div>
                                <div class="stat-label">Assignments</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
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
            
            <?php if (empty($publisher_campaigns)): ?>
                <div class="card"><div class="card-body text-center p-5 text-muted">No publishers found.</div></div>
            <?php else: ?>
                <?php foreach ($publisher_campaigns as $publisher): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-1"><?php echo htmlspecialchars($publisher['name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($publisher['email']); ?></small>
                            <?php if ($publisher['website']): ?>
                                <br><small><a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank"><?php echo htmlspecialchars($publisher['website']); ?></a></small>
                            <?php endif; ?>
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
                                                <th>Publisher Clicks</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($publisher['campaigns'] as $campaign): ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                                <td><code><?php echo htmlspecialchars($campaign['publisher_shortcode'] ?? 'N/A'); ?></code></td>
                                                <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                                <td class="fw-bold text-primary"><?php echo number_format($campaign['publisher_clicks']); ?></td>
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