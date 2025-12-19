<?php
// admin/payment_reports.php - Payment Reports (Modern UI)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Payment Reports';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get admin permissions
$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);

$error = '';
$success = '';

// Handle lead updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_leads') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $target_leads = $_POST['target_leads'] ?? 0;
    $validated_leads = $_POST['validated_leads'] ?? 0;
    
    if (is_numeric($target_leads) && is_numeric($validated_leads) && $target_leads >= 0 && $validated_leads >= 0) {
        try {
            $stmt = $conn->prepare("UPDATE campaigns SET target_leads = ?, validated_leads = ? WHERE id = ?");
            $stmt->execute([$target_leads, $validated_leads, $campaign_id]);
            $_SESSION['success_message'] = "Lead information updated successfully.";
            header('Location: payment_reports.php');
            exit();
        } catch (PDOException $e) {
            $error = "Error updating lead information: " . $e->getMessage();
        }
    } else {
        $error = "Invalid lead values. Please enter valid numbers.";
    }
}

// Handle payment status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_payment_status') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    $new_status = ($current_status === 'pending') ? 'completed' : 'pending';
    
    try {
        $stmt = $conn->prepare("UPDATE campaigns SET payment_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $campaign_id]);
        $_SESSION['success_message'] = "Payment status updated successfully.";
        header('Location: payment_reports.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating payment status: " . $e->getMessage();
    }
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get filter parameter
$filter_status = $_GET['status'] ?? 'all';

// Get payment report data
try {
    $sql = "
        SELECT 
            c.id, c.name as campaign_name, c.shortcode, c.advertiser_payout, c.publisher_payout,
            c.campaign_type, c.click_count, c.target_leads, c.validated_leads, c.payment_status,
            c.start_date, c.end_date,
            GROUP_CONCAT(DISTINCT a.name) as advertiser_names,
            GROUP_CONCAT(DISTINCT p.name) as publisher_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        LEFT JOIN publishers p ON cp.publisher_id = p.id
    ";
    
    if ($filter_status !== 'all') {
        $sql .= " WHERE c.payment_status = ? ";
    }
    
    $sql .= " GROUP BY c.id ORDER BY c.created_at DESC ";
    
    $stmt = $conn->prepare($sql);
    if ($filter_status !== 'all') {
        $stmt->execute([$filter_status]);
    } else {
        $stmt->execute();
    }
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_advertiser_payout = 0;
    $total_publisher_payout = 0;
    $pending_payments = 0;
    $completed_payments = 0;
    
    foreach ($campaigns as $campaign) {
        if ($campaign['payment_status'] === 'pending') {
            $pending_payments++;
        } else {
            $completed_payments++;
        }
        $total_advertiser_payout += $campaign['advertiser_payout'];
        $total_publisher_payout += $campaign['publisher_payout'];
    }
    
} catch (PDOException $e) {
    $error = "Error loading payment reports: " . $e->getMessage();
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-file-invoice-dollar me-2"></i>Payment Reports</h2>
                    <p class="text-muted mb-0">Track campaign payments and leads</p>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Filter by Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Payments</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed Payments</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter me-1"></i>Apply Filter</button>
                            <a href="payment_reports.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo count($campaigns); ?></div>
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
                                <div class="value"><?php echo $pending_payments; ?></div>
                                <div class="label">Pending Payments</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $completed_payments; ?></div>
                                <div class="label">Completed Payments</div>
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
                                <div class="value">$<?php echo number_format($total_advertiser_payout, 2); ?></div>
                                <div class="label">Total Advertiser Payout</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Reports Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-table me-2"></i>Campaign Payment Details</span>
                    <span class="badge bg-primary"><?php echo count($campaigns); ?> campaigns</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($campaigns)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No campaigns found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Type</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Validated</th>
                                        <th class="text-end">Total Amount</th>
                                        <th class="text-center">Clicks</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?php echo htmlspecialchars($campaign['campaign_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></td>
                                        <td class="text-center"><?php echo $campaign['target_leads']; ?></td>
                                        <td class="text-center fw-bold text-success"><?php echo $campaign['validated_leads']; ?></td>
                                        <td class="text-end fw-bold">$<?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></td>
                                        <td class="text-center"><?php echo number_format($campaign['click_count']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $campaign['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($campaign['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#leadModal<?php echo $campaign['id']; ?>" title="Update Leads">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Change payment status?');">
                                                <input type="hidden" name="action" value="toggle_payment_status">
                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $campaign['payment_status']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $campaign['payment_status'] === 'completed' ? 'warning' : 'success'; ?>" title="<?php echo $campaign['payment_status'] === 'completed' ? 'Mark Pending' : 'Mark Completed'; ?>">
                                                    <i class="fas fa-<?php echo $campaign['payment_status'] === 'completed' ? 'undo' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    
                                    <!-- Lead Update Modal -->
                                    <div class="modal fade" id="leadModal<?php echo $campaign['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                                                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Lead Information</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_leads">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Campaign</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($campaign['campaign_name']); ?>" disabled>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Target Leads</label>
                                                            <input type="number" class="form-control" name="target_leads" value="<?php echo $campaign['target_leads']; ?>" min="0">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Validated Leads</label>
                                                            <input type="number" class="form-control" name="validated_leads" value="<?php echo $campaign['validated_leads']; ?>" min="0">
                                                        </div>
                                                        
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            <strong>Advertiser Payout:</strong> $<?php echo number_format($campaign['advertiser_payout'], 2); ?> per lead
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
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
