<?php
// admin/manage_publishers.php - Manage Publishers (Modern UI)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Manage Publishers';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get admin permissions
$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);

$error = '';
$success = '';

// Handle publisher creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Name, email, and password are required.';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO publishers (name, email, password, website, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $password, $website, $phone]);
                $_SESSION['success_message'] = "Publisher created successfully.";
                header('Location: manage_publishers.php');
                exit();
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "A publisher with this email already exists.";
                } else {
                    $error = "Error creating publisher: " . $e->getMessage();
                }
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $publisher_id = $_POST['publisher_id'] ?? '';
        if (!empty($publisher_id)) {
            try {
                $stmt = $conn->prepare("DELETE FROM publishers WHERE id = ?");
                $stmt->execute([$publisher_id]);
                $_SESSION['success_message'] = "Publisher deleted successfully.";
                header('Location: manage_publishers.php');
                exit();
            } catch (PDOException $e) {
                $error = "Error deleting publisher.";
            }
        }
    }
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all publishers with stats
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(DISTINCT cp.campaign_id) as campaign_count,
           COALESCE(SUM(pdc.clicks), 0) as total_clicks
    FROM publishers p
    LEFT JOIN campaign_publishers cp ON p.id = cp.publisher_id
    LEFT JOIN publisher_daily_clicks pdc ON p.id = pdc.publisher_id
    GROUP BY p.id
    ORDER BY p.name
");
$stmt->execute();
$publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_publishers = count($publishers);

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
                    <h2 class="mb-1"><i class="fas fa-users me-2"></i>Manage Publishers</h2>
                    <p class="text-muted mb-0">View and manage all publishers</p>
                </div>
                <?php if ($is_super_admin || in_array('publishers_create', $admin_permissions)): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPublisherModal">
                    <i class="fas fa-plus me-2"></i>Add Publisher
                </button>
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
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo $total_publishers; ?></div>
                                <div class="label">Total Publishers</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo number_format(array_sum(array_column($publishers, 'total_clicks'))); ?></div>
                                <div class="label">Total Clicks</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-mouse-pointer"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="value"><?php echo array_sum(array_column($publishers, 'campaign_count')); ?></div>
                                <div class="label">Campaign Assignments</div>
                            </div>
                            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Publishers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>All Publishers</span>
                    <span class="badge bg-primary"><?php echo $total_publishers; ?> publishers</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($publishers)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>No publishers found. Add your first publisher.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Publisher</th>
                                        <th>Email</th>
                                        <th>Website</th>
                                        <th>Phone</th>
                                        <th class="text-center">Campaigns</th>
                                        <th class="text-center">Clicks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo $publisher['id']; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon me-2" style="width:35px;height:35px;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#6366f1);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;">
                                                    <?php echo strtoupper(substr($publisher['name'], 0, 1)); ?>
                                                </div>
                                                <span class="fw-medium"><?php echo htmlspecialchars($publisher['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($publisher['email']); ?></td>
                                        <td>
                                            <?php if (!empty($publisher['website'])): ?>
                                                <a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank" class="text-decoration-none">
                                                    <i class="fas fa-external-link-alt me-1"></i>Visit
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($publisher['phone'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $publisher['campaign_count']; ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?php echo number_format($publisher['total_clicks']); ?></td>
                                        <td class="text-center">
                                            <?php if ($is_super_admin || in_array('publishers_delete', $admin_permissions)): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this publisher?')">
                                                <input type="hidden" name="publisher_id" value="<?php echo $publisher['id']; ?>">
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

<!-- Create Publisher Modal -->
<div class="modal fade" id="createPublisherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Publisher</h5>
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
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" name="website" placeholder="https://example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Publisher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
