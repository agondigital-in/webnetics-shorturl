<?php
// super_admin/campaigns.php - Campaign Management
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Campaigns';
$success = '';
$error = '';

// Handle status toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $new_status = $_GET['toggle_status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $_GET['id']]);
        $success = "Campaign status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $success = "Campaign deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting campaign: " . $e->getMessage();
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all campaigns with advertiser and publisher counts
    $stmt = $conn->prepare("
        SELECT c.*, 
               COUNT(DISTINCT ca.advertiser_id) as advertiser_count,
               COUNT(DISTINCT cp.publisher_id) as publisher_count
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading campaigns: " . $e->getMessage();
    $campaigns = [];
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
                    <h2><i class="fas fa-bullhorn me-2"></i>Campaigns</h2>
                    <p>Manage all advertising campaigns</p>
                </div>
                <a href="add_campaign.php" class="btn btn-primary-custom">
                    <i class="fas fa-plus me-2"></i>Add Campaign
                </a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($campaigns)): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No campaigns yet</h5>
                            <p class="text-muted">Create your first campaign to get started.</p>
                            <a href="add_campaign.php" class="btn btn-primary-custom">Add Campaign</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign Name</th>
                                        <th>Short Code</th>
                                        <th>Type</th>
                                        <th>Clicks</th>
                                        <th>Advertisers</th>
                                        <th>Publishers</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td><?php echo $campaign['id']; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($campaign['name']); ?></td>
                                        <td><code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                        <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                        <td><span class="fw-bold text-primary"><?php echo number_format($campaign['click_count']); ?></span></td>
                                        <td><?php echo $campaign['advertiser_count']; ?></td>
                                        <td><?php echo $campaign['publisher_count']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="campaign_tracking_stats.php?id=<?php echo $campaign['id']; ?>" class="btn btn-info text-white" title="Stats">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                                <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-warning text-white" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?toggle_status=<?php echo $campaign['status']; ?>&id=<?php echo $campaign['id']; ?>" class="btn btn-<?php echo $campaign['status'] === 'active' ? 'secondary' : 'success'; ?>" title="Toggle Status">
                                                    <i class="fas fa-<?php echo $campaign['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </a>
                                                <a href="?delete=1&id=<?php echo $campaign['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this campaign?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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