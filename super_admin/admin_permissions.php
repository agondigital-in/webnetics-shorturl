<?php
// super_admin/admin_permissions.php - Manage Admin Permissions
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Admin Permissions';
$db = Database::getInstance();
$conn = $db->getConnection();

$error = '';
$success = '';

// Available permissions - grouped by category
$available_permissions = [
    // Campaigns
    'campaigns_view' => 'View Campaigns',
    'campaigns_create' => 'Create Campaigns',
    'campaigns_edit' => 'Edit Campaigns',
    'campaigns_delete' => 'Delete Campaigns',
    // Publishers
    'publishers_view' => 'View Publishers',
    'publishers_create' => 'Create Publishers',
    'publishers_edit' => 'Edit Publishers',
    'publishers_delete' => 'Delete Publishers',
    // Advertisers
    'advertisers_view' => 'View Advertisers',
    'advertisers_create' => 'Create Advertisers',
    'advertisers_edit' => 'Edit Advertisers',
    'advertisers_delete' => 'Delete Advertisers',
    // Admins Management
    'admins_view' => 'View Admins',
    'admins_create' => 'Create Admins',
    'admins_edit' => 'Edit Admins',
    'admins_delete' => 'Delete Admins',
    // Campaign Assignments
    'advertiser_campaigns_view' => 'View Advertiser Campaigns',
    'advertiser_campaigns_edit' => 'Edit Advertiser Campaigns',
    'publisher_campaigns_view' => 'View Publisher Campaigns',
    'publisher_campaigns_edit' => 'Edit Publisher Campaigns',
    // Stats & Reports
    'stats_view' => 'View Statistics',
    'publishers_stats_view' => 'View Publishers Stats',
    'reports_view' => 'View Reports',
    'reports_export' => 'Export Reports',
    // System Settings
    'security_view' => 'View Security Settings',
    'security_edit' => 'Edit Security Settings',
    'db_backup_view' => 'View DB Backup',
    'db_backup_create' => 'Create DB Backup',
    'admin_permissions_view' => 'View Admin Permissions',
    'admin_permissions_edit' => 'Edit Admin Permissions',
];

// Check if admin_permissions table exists, create if not
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS `admin_permissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `admin_id` INT NOT NULL,
        `permission` VARCHAR(50) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_admin_permission` (`admin_id`, `permission`),
        INDEX `idx_admin_id` (`admin_id`)
    )");
} catch (Exception $e) {
    // Table might already exist
}

// Handle permission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'])) {
    $admin_id = intval($_POST['admin_id']);
    $permissions = $_POST['permissions'] ?? [];
    $redirect_to = $_POST['redirect_to'] ?? 'admin_permissions.php';
    
    try {
        // Delete existing permissions
        $stmt = $conn->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        
        // Insert new permissions
        if (!empty($permissions)) {
            $stmt = $conn->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
            foreach ($permissions as $perm) {
                if (array_key_exists($perm, $available_permissions)) {
                    $stmt->execute([$admin_id, $perm]);
                }
            }
        }
        
        $_SESSION['success_message'] = "Permissions updated successfully!";
        header('Location: ' . $redirect_to);
        exit();
    } catch (Exception $e) {
        $error = "Error updating permissions: " . $e->getMessage();
    }
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all admins
$stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'admin' ORDER BY username");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get permissions for each admin
$admin_permissions = [];
foreach ($admins as $admin) {
    $stmt = $conn->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
    $stmt->execute([$admin['id']]);
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $admin_permissions[$admin['id']] = $perms;
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-user-cog me-2"></i>Admin Permissions</h2>
                <p>Manage what each admin can access and do</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (empty($admins)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No admins found. <a href="admins.php">Create an admin first</a>.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($admins as $admin): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($admin['username']); ?></span>
                                <span class="badge bg-light text-primary">Admin</span>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    
                                    <!-- Campaigns -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-bullhorn me-2"></i>Campaigns</h6>
                                        <div class="row">
                                            <?php foreach (['campaigns_view', 'campaigns_create', 'campaigns_edit', 'campaigns_delete'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Publishers -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-users me-2"></i>Publishers</h6>
                                        <div class="row">
                                            <?php foreach (['publishers_view', 'publishers_create', 'publishers_edit', 'publishers_delete'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Advertisers -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-ad me-2"></i>Advertisers</h6>
                                        <div class="row">
                                            <?php foreach (['advertisers_view', 'advertisers_create', 'advertisers_edit', 'advertisers_delete'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Admins Management -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-user-shield me-2"></i>Admins</h6>
                                        <div class="row">
                                            <?php foreach (['admins_view', 'admins_create', 'admins_edit', 'admins_delete'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Campaign Assignments -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-link me-2"></i>Campaign Assignments</h6>
                                        <div class="row">
                                            <?php foreach (['advertiser_campaigns_view', 'advertiser_campaigns_edit', 'publisher_campaigns_view', 'publisher_campaigns_edit'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Reports & Stats -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-chart-bar me-2"></i>Reports & Stats</h6>
                                        <div class="row">
                                            <?php foreach (['stats_view', 'publishers_stats_view', 'reports_view', 'reports_export'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- System Settings -->
                                    <div class="mb-3">
                                        <h6 class="text-muted"><i class="fas fa-cog me-2"></i>System Settings</h6>
                                        <div class="row">
                                            <?php foreach (['security_view', 'security_edit', 'db_backup_view', 'db_backup_create', 'admin_permissions_view', 'admin_permissions_edit'] as $perm): ?>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_permissions[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>">
                                                        <?php echo $available_permissions[$perm]; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save me-1"></i>Save Permissions
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll(<?php echo $admin['id']; ?>)">
                                            <i class="fas fa-check-double me-1"></i>Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAll(<?php echo $admin['id']; ?>)">
                                            <i class="fas fa-times me-1"></i>Clear All
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Permission Legend -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Permission Guide
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="fas fa-eye text-info me-2"></i>View</h6>
                            <p class="small text-muted">Can see the data but cannot make changes</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-plus text-success me-2"></i>Create</h6>
                            <p class="small text-muted">Can add new records</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-edit text-warning me-2"></i>Edit</h6>
                            <p class="small text-muted">Can modify existing records</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-trash text-danger me-2"></i>Delete</h6>
                            <p class="small text-muted">Can remove records permanently</p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-download text-primary me-2"></i>Export</h6>
                            <p class="small text-muted">Can download/export data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll(adminId) {
    document.querySelectorAll('input[id^="' + adminId + '_"]').forEach(cb => cb.checked = true);
}

function deselectAll(adminId) {
    document.querySelectorAll('input[id^="' + adminId + '_"]').forEach(cb => cb.checked = false);
}
</script>

<?php require_once 'includes/footer.php'; ?>
