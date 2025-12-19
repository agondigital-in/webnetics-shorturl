<?php
// admin/manage_advertisers.php - Manage Advertisers (Modern UI)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Manage Advertisers';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get admin permissions
$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);

$error = '';
$success = '';

// Handle advertiser creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO advertisers (name, email, company, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $company, $phone]);
                $_SESSION['success_message'] = "Advertiser created successfully.";
                header('Location: manage_advertisers.php');
                exit();
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "An advertiser with this email already exists.";
                } else {
                    $error = "Error creating advertiser: " . $e->getMessage();
                }
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $advertiser_id = $_POST['advertiser_id'] ?? '';
        if (!empty($advertiser_id)) {
            try {
                $stmt = $conn->prepare("DELETE FROM advertisers WHERE id = ?");
                $stmt->execute([$advertiser_id]);
                $_SESSION['success_message'] = "Advertiser deleted successfully.";
                header('Location: manage_advertisers.php');
                exit();
            } catch (PDOException $e) {
                $error = "Error deleting advertiser.";
            }
        }
    }
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all advertisers with stats
$stmt = $conn->prepare("
    SELECT a.*, 
           COUNT(DISTINCT ca.campaign_id) as campaign_count
    FROM advertisers a
    LEFT JOIN campaign_advertisers ca ON a.id = ca.advertiser_id
    GROUP BY a.id
    ORDER BY a.name
");
$stmt->execute();
$advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_advertisers = count($advertisers);

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <?php 
            $is_super_admin = ($_SESSION['role'] === 'super_admin');
            ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-ad me-2"></i>Manage Advertisers</h2>
                    <p class="text-muted mb-0">View and manage all advertisers</p>
                </div>
                <?php if ($is_super_admin || in_array('advertisers_create', $admin_permissions)): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdvertiserModal">
                    <i class="fas fa-plus me-2"></i>Add Advertiser
                </button>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $total_advertisers; ?></div>
                                <div class="label">Total Advertisers</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                                <i class="fas fa-ad"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo array_sum(array_column($advertisers, 'campaign_count')); ?></div>
                                <div class="label">Campaign Assignments</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo count(array_filter($advertisers, fn($a) => !empty($a['company']))); ?></div>
                                <div class="label">With Company</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Advertisers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>All Advertisers</span>
                    <span class="badge bg-primary"><?php echo $total_advertisers; ?> advertisers</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($advertisers)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-ad fa-3x mb-3"></i>
                            <p>No advertisers found. Add your first advertiser.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Advertiser</th>
                                        <th>Email</th>
                                        <th>Company</th>
                                        <th>Phone</th>
                                        <th class="text-center">Campaigns</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($advertisers as $advertiser): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo $advertiser['id']; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon me-2" style="width:35px;height:35px;border-radius:8px;background:linear-gradient(135deg,#06b6d4,#0891b2);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;">
                                                    <?php echo strtoupper(substr($advertiser['name'], 0, 1)); ?>
                                                </div>
                                                <span class="fw-medium"><?php echo htmlspecialchars($advertiser['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($advertiser['email']); ?></td>
                                        <td><?php echo htmlspecialchars($advertiser['company'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($advertiser['phone'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $advertiser['campaign_count']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($is_super_admin || in_array('advertisers_delete', $admin_permissions)): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this advertiser?')">
                                                <input type="hidden" name="advertiser_id" value="<?php echo $advertiser['id']; ?>">
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

<!-- Create Advertiser Modal -->
<div class="modal fade" id="createAdvertiserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Advertiser</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" class="form-control" name="company">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Advertiser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
