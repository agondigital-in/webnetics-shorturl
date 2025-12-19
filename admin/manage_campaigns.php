<?php
// admin/manage_campaigns.php - Manage campaigns (Modern UI)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Manage Campaigns';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get admin permissions
$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

$error = '';
$success = '';

// Handle campaign status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $campaign_id = $_POST['campaign_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (!empty($campaign_id) && in_array($status, ['active', 'inactive'])) {
            try {
                $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
                $stmt->execute([$status, $campaign_id]);
                $_SESSION['success_message'] = "Campaign status updated successfully.";
                header('Location: manage_campaigns.php');
                exit();
            } catch (PDOException $e) {
                $error = "Error updating campaign status.";
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $campaign_id = $_POST['campaign_id'] ?? '';
        if (!empty($campaign_id)) {
            try {
                $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
                $stmt->execute([$campaign_id]);
                $_SESSION['success_message'] = "Campaign deleted successfully.";
                header('Location: manage_campaigns.php');
                exit();
            } catch (PDOException $e) {
                $error = "Error deleting campaign.";
            }
        }
    }
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all campaigns with stats
$stmt = $conn->prepare("
    SELECT c.*, 
           GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as advertiser_names,
           GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as publisher_names,
           COUNT(DISTINCT cp.publisher_id) as publisher_count,
           COALESCE(c.conversion_count, 0) as conversions
    FROM campaigns c
    LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
    LEFT JOIN advertisers a ON ca.advertiser_id = a.id
    LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
    LEFT JOIN publishers p ON cp.publisher_id = p.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_campaigns = count($campaigns);
$active_campaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
$total_clicks = array_sum(array_column($campaigns, 'click_count'));
$total_conversions = array_sum(array_column($campaigns, 'conversions'));

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-bullhorn me-2"></i>Manage Campaigns</h2>
                    <p class="text-muted mb-0">View and manage all campaigns</p>
                </div>
                <?php if ($is_super_admin || in_array('campaigns_create', $admin_permissions)): ?>
                <a href="create_campaign.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Campaign
                </a>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $total_campaigns; ?></div>
                                <div class="label">Total Campaigns</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
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
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($total_clicks); ?></div>
                                <div class="label">Total Clicks</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-mouse-pointer"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format($total_conversions); ?></div>
                                <div class="label">Conversions</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campaigns Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>All Campaigns</span>
                    <span class="badge bg-primary"><?php echo $total_campaigns; ?> campaigns</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($campaigns)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No campaigns found. <a href="create_campaign.php">Create your first campaign</a>.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Shortcode</th>
                                        <th>Type</th>
                                        <th>Publishers</th>
                                        <th class="text-center">Clicks</th>
                                        <th class="text-center">Conv.</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?php echo htmlspecialchars($campaign['name']); ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width:200px;"><?php echo htmlspecialchars($campaign['target_url']); ?></small>
                                        </td>
                                        <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                        <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                        <td><span class="badge bg-info"><?php echo $campaign['publisher_count']; ?> publishers</span></td>
                                        <td class="text-center fw-bold text-primary"><?php echo number_format($campaign['click_count']); ?></td>
                                        <td class="text-center fw-bold text-success"><?php echo number_format($campaign['conversions']); ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M d', strtotime($campaign['start_date'])); ?> - 
                                                <?php echo date('M d', strtotime($campaign['end_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($is_super_admin || in_array('campaigns_edit', $admin_permissions)): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <select name="status" class="form-select form-select-sm" style="width: 100px;" onchange="this.form.submit()">
                                                    <option value="active" <?php echo $campaign['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $campaign['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </form>
                                            <?php else: ?>
                                            <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($is_super_admin || in_array('stats_view', $admin_permissions)): ?>
                                            <a href="campaign_tracking_stats.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-info" title="View Stats">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($is_super_admin || in_array('campaigns_delete', $admin_permissions)): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
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

<?php require_once 'includes/footer.php'; ?>
