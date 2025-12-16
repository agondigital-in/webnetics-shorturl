<?php
// admin/payment_reports.php - Payment Reports for Admins
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Handle lead updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_leads') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $target_leads = $_POST['target_leads'] ?? 0;
    $validated_leads = $_POST['validated_leads'] ?? 0;
    
    // Validate inputs
    if (is_numeric($target_leads) && is_numeric($validated_leads) && $target_leads >= 0 && $validated_leads >= 0) {
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
        $error = "Invalid lead values. Please enter valid numbers.";
    }
}

// Handle payment status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_payment_status') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    
    // Toggle status
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

// Get filter parameter
$filter_status = $_GET['status'] ?? 'all';

// Get payment report data
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Build query based on filter
    $sql = "
        SELECT 
            c.id,
            c.name as campaign_name,
            c.shortcode,
            c.advertiser_payout,
            c.publisher_payout,
            c.campaign_type,
            c.click_count,
            c.target_leads,
            c.validated_leads,
            c.payment_status,
            c.start_date,
            c.end_date,
            GROUP_CONCAT(DISTINCT a.name) as advertiser_names,
            GROUP_CONCAT(DISTINCT p.name) as publisher_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        LEFT JOIN publishers p ON cp.publisher_id = p.id
        ";
    
    // Add filter condition if needed
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .filter-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .table th {
            background-color: #f8f9fa;
        }
        .modal-content {
            border: 1px solid rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                <a class="nav-link btn btn-outline-light" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Navigation</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="create_campaign.php" class="list-group-item list-group-item-action">Manage Campaigns</a>
                        <a href="manage_advertisers.php" class="list-group-item list-group-item-action">Manage Advertisers</a>
                        <a href="manage_publishers.php" class="list-group-item list-group-item-action">Manage Publishers</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2>Payment Reports</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <!-- Filter Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card filter-card">
                            <div class="card-body">
                                <h5 class="card-title">Filter Campaigns</h5>
                                <form method="GET" class="row">
                                    <div class="col-md-4">
                                        <select name="status" class="form-select">
                                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Payments</option>
                                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed Payments</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                                        <a href="payment_reports.php" class="btn btn-secondary">Clear Filter</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary summary-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($campaigns); ?></h5>
                                <p class="card-text">Total Campaigns</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning summary-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $pending_payments; ?></h5>
                                <p class="card-text">Pending Payments</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success summary-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $completed_payments; ?></h5>
                                <p class="card-text">Completed Payments</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info summary-card">
                            <div class="card-body">
                                <h5 class="card-title">$<?php echo number_format($total_advertiser_payout, 2); ?></h5>
                                <p class="card-text">Total Advertiser Payout</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Campaign Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($campaigns)): ?>
                            <p>No campaigns found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Campaign Name</th>
                                            <th>Advertisers</th>
                                            <th>Publishers</th>
                                            <th>Type</th>
                                            <th>Target Leads</th>
                                            <th>Validated Leads</th>
                                            <th>Total Amount</th>
                                            <th>Clicks</th>
                                            <th>Advertiser Payout</th>
                                            <th>Publisher Payout</th>
                                            <th>Payment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                                                <td><?php echo $campaign['target_leads']; ?></td>
                                                <td><?php echo $campaign['validated_leads']; ?></td>
                                                <td>$<?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></td>
                                                <td><?php echo $campaign['click_count']; ?></td>
                                                <td>$<?php echo number_format($campaign['advertiser_payout'], 2); ?></td>
                                                <td>$<?php echo number_format($campaign['publisher_payout'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $campaign['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($campaign['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <!-- Lead Update Modal Trigger -->
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#leadModal<?php echo $campaign['id']; ?>">
                                                        Update Leads
                                                    </button>
                                                    
                                                    <!-- Payment Status Toggle -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to change the payment status?');">
                                                        <input type="hidden" name="action" value="toggle_payment_status">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $campaign['payment_status']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-<?php echo $campaign['payment_status'] === 'completed' ? 'warning' : 'success'; ?>">
                                                            <?php echo $campaign['payment_status'] === 'completed' ? 'Mark Pending' : 'Mark Completed'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            
                                            <!-- Lead Update Modal -->
                                            <div class="modal fade" id="leadModal<?php echo $campaign['id']; ?>" tabindex="-1" aria-labelledby="leadModalLabel<?php echo $campaign['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="leadModalLabel<?php echo $campaign['id']; ?>">Update Lead Information</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update_leads">
                                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="target_leads_<?php echo $campaign['id']; ?>" class="form-label">Target Leads</label>
                                                                    <input type="number" class="form-control" id="target_leads_<?php echo $campaign['id']; ?>" name="target_leads" value="<?php echo $campaign['target_leads']; ?>" min="0">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="validated_leads_<?php echo $campaign['id']; ?>" class="form-label">Validated Leads</label>
                                                                    <input type="number" class="form-control" id="validated_leads_<?php echo $campaign['id']; ?>" name="validated_leads" value="<?php echo $campaign['validated_leads']; ?>" min="0">
                                                                </div>
                                                                
                                                                <div class="alert alert-info">
                                                                    <strong>Total Amount:</strong> $<?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?>
                                                                    <br>(Validated Leads × Advertiser Payout)
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update total amount when validated leads change
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all validated leads inputs
            const validatedLeadsInputs = document.querySelectorAll('input[name="validated_leads"]');
            
            validatedLeadsInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const modalBody = this.closest('.modal-body');
                    const advertiserPayoutText = modalBody.querySelector('.alert').innerHTML;
                    const advertiserPayoutMatch = advertiserPayoutText.match(/\$(\d+\.\d+)/);
                    
                    if (advertiserPayoutMatch) {
                        const advertiserPayout = parseFloat(advertiserPayoutMatch[1]);
                        const validatedLeads = parseFloat(this.value) || 0;
                        const totalAmount = validatedLeads * advertiserPayout;
                        
                        // Update the total amount display
                        const alertElement = modalBody.querySelector('.alert');
                        alertElement.innerHTML = `<strong>Total Amount:</strong> $${totalAmount.toFixed(2)}<br>(Validated Leads × Advertiser Payout)`;
                    }
                });
            });
        });
    </script>
</body>
</html>