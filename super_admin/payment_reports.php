<?php
// super_admin/payment_reports.php - Payment Reports
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Payment Reports';
$success = '';
$error = '';

// Handle lead updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_leads') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $target_leads = $_POST['target_leads'] ?? 0;
    $validated_leads = $_POST['validated_leads'] ?? 0;
    
    if (is_numeric($target_leads) && is_numeric($validated_leads) && $target_leads >= 0 && $validated_leads >= 0 && $validated_leads <= $target_leads) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE campaigns SET target_leads = ?, validated_leads = ? WHERE id = ?");
            $stmt->execute([$target_leads, $validated_leads, $campaign_id]);
            $success = "Lead information updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating lead information: " . $e->getMessage();
        }
    } else {
        $error = "Invalid lead values.";
    }
}

// Handle payment status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_payment_status') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    $new_status = ($current_status === 'pending') ? 'completed' : 'pending';
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE campaigns SET payment_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $campaign_id]);
        $success = "Payment status updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating payment status: " . $e->getMessage();
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$quick_filter = $_GET['quick_filter'] ?? '';
$filter_date_value = '';

if (!empty($quick_filter)) {
    switch ($quick_filter) {
        case 'today': $filter_date_value = date('Y-m-d'); break;
        case 'yesterday': $filter_date_value = date('Y-m-d', strtotime('-1 day')); break;
        case 'month': $filter_date_value = date('Y-m'); break;
        case 'year': $filter_date_value = date('Y'); break;
    }
}

// Get campaigns
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT c.*, GROUP_CONCAT(DISTINCT a.name) as advertiser_names, GROUP_CONCAT(DISTINCT p.name) as publisher_names
            FROM campaigns c
            LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
            LEFT JOIN advertisers a ON ca.advertiser_id = a.id
            LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
            LEFT JOIN publishers p ON cp.publisher_id = p.id";
    
    $where = [];
    $params = [];
    
    if ($filter_status !== 'all') {
        $where[] = "c.payment_status = ?";
        $params[] = $filter_status;
    }
    
    if (!empty($filter_date_value)) {
        if (strlen($filter_date_value) === 10) {
            $where[] = "DATE(c.created_at) = ?";
        } elseif (strlen($filter_date_value) === 7) {
            $where[] = "DATE_FORMAT(c.created_at, '%Y-%m') = ?";
        } else {
            $where[] = "YEAR(c.created_at) = ?";
        }
        $params[] = $filter_date_value;
    }
    
    if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_advertiser_payout = 0;
    $total_publisher_payout = 0;
    $pending_payments = 0;
    $completed_payments = 0;
    
    foreach ($campaigns as $c) {
        $c['payment_status'] === 'pending' ? $pending_payments++ : $completed_payments++;
        $total_advertiser_payout += $c['advertiser_payout'];
        $total_publisher_payout += $c['publisher_payout'];
    }
    
} catch (PDOException $e) {
    $error = "Error loading payment reports: " . $e->getMessage();
    $campaigns = [];
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-file-invoice-dollar me-2"></i>Payment Reports</h2>
                <p>Manage campaign payments and leads</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <a href="?quick_filter=today" class="btn btn-sm btn-outline-primary <?php echo $quick_filter === 'today' ? 'active' : ''; ?>">Today</a>
                        <a href="?quick_filter=yesterday" class="btn btn-sm btn-outline-primary <?php echo $quick_filter === 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
                        <a href="?quick_filter=month" class="btn btn-sm btn-outline-primary <?php echo $quick_filter === 'month' ? 'active' : ''; ?>">This Month</a>
                        <a href="?quick_filter=year" class="btn btn-sm btn-outline-primary <?php echo $quick_filter === 'year' ? 'active' : ''; ?>">This Year</a>
                        <a href="payment_reports.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                        
                        <select class="form-select form-select-sm" style="width: auto;" onchange="window.location='?status='+this.value">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="stat-value"><?php echo count($campaigns); ?></div><div class="stat-label">Campaigns</div></div>
                            <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="stat-value"><?php echo $pending_payments; ?></div><div class="stat-label">Pending</div></div>
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="stat-value"><?php echo $completed_payments; ?></div><div class="stat-label">Completed</div></div>
                            <div class="stat-icon"><i class="fas fa-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="stat-value">₹<?php echo number_format($total_advertiser_payout - $total_publisher_payout, 0); ?></div><div class="stat-label">Profit</div></div>
                            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campaigns Table -->
            <div class="card">
                <div class="card-header">Campaign Payment Details</div>
                <div class="card-body p-0">
                    <?php if (empty($campaigns)): ?>
                        <div class="p-4 text-center text-muted">No campaigns found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Type</th>
                                        <th>Target</th>
                                        <th>Validated</th>
                                        <th>Total Amount</th>
                                        <th>Clicks</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($campaign['name']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $campaign['campaign_type']; ?></span></td>
                                        <td><?php echo $campaign['target_leads']; ?></td>
                                        <td><?php echo $campaign['validated_leads']; ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></td>
                                        <td><?php echo number_format($campaign['click_count']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $campaign['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($campaign['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#leadModal<?php echo $campaign['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Change payment status?');">
                                                <input type="hidden" name="action" value="toggle_payment_status">
                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $campaign['payment_status']; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $campaign['payment_status'] === 'completed' ? 'warning' : 'success'; ?>">
                                                    <i class="fas fa-<?php echo $campaign['payment_status'] === 'completed' ? 'undo' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    
                                    <!-- Lead Modal -->
                                    <div class="modal fade" id="leadModal<?php echo $campaign['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Leads - <?php echo htmlspecialchars($campaign['name']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_leads">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Target Leads</label>
                                                            <input type="number" class="form-control" name="target_leads" value="<?php echo $campaign['target_leads']; ?>" min="0">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Validated Leads</label>
                                                            <input type="number" class="form-control" name="validated_leads" value="<?php echo $campaign['validated_leads']; ?>" min="0">
                                                        </div>
                                                        <div class="alert alert-info">
                                                            Advertiser Payout: ₹<?php echo number_format($campaign['advertiser_payout'], 2); ?>/lead
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary-custom">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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