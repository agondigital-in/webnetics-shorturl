<?php
// admin/admin_permissions.php - Admin Permissions (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Admin Permissions';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions_list = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('admin_permissions_view', $admin_permissions_list)) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Available permissions
$available_permissions = [
    'campaigns_view' => 'View Campaigns',
    'campaigns_create' => 'Create Campaigns',
    'campaigns_edit' => 'Edit Campaigns',
    'campaigns_delete' => 'Delete Campaigns',
    'publishers_view' => 'View Publishers',
    'publishers_create' => 'Create Publishers',
    'publishers_edit' => 'Edit Publishers',
    'publishers_delete' => 'Delete Publishers',
    'advertisers_view' => 'View Advertisers',
    'advertisers_create' => 'Create Advertisers',
    'advertisers_edit' => 'Edit Advertisers',
    'advertisers_delete' => 'Delete Advertisers',
    'admins_view' => 'View Admins',
    'admins_create' => 'Create Admins',
    'admins_edit' => 'Edit Admins',
    'admins_delete' => 'Delete Admins',
    'advertiser_campaigns_view' => 'View Advertiser Campaigns',
    'advertiser_campaigns_edit' => 'Edit Advertiser Campaigns',
    'publisher_campaigns_view' => 'View Publisher Campaigns',
    'publisher_campaigns_edit' => 'Edit Publisher Campaigns',
    'stats_view' => 'View Statistics',
    'publishers_stats_view' => 'View Publishers Stats',
    'reports_view' => 'View Reports',
    'reports_export' => 'Export Reports',
    'security_view' => 'View Security Settings',
    'security_edit' => 'Edit Security Settings',
    'db_backup_view' => 'View DB Backup',
    'db_backup_create' => 'Create DB Backup',
    'admin_permissions_view' => 'View Admin Permissions',
    'admin_permissions_edit' => 'Edit Admin Permissions',
];

// Handle permission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'])) {
    if (!$is_super_admin && !in_array('admin_permissions_edit', $admin_permissions_list)) {
        $error = 'You do not have permission to edit admin permissions.';
    } else {
        $admin_id = intval($_POST['admin_id']);
        $permissions = $_POST['permissions'] ?? [];
        
        try {
            $stmt = $conn->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
            
            if (!empty($permissions)) {
                $stmt = $conn->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
                foreach ($permissions as $perm) {
                    if (array_key_exists($perm, $available_permissions)) {
                        $stmt->execute([$admin_id, $perm]);
                    }
                }
            }
            
            $_SESSION['success_message'] = "Permissions updated successfully!";
            header('Location: admin_permissions.php');
            exit();
        } catch (Exception $e) {
            $error = "Error updating permissions: " . $e->getMessage();
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all admins
$stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'admin' ORDER BY username");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get permissions for each admin
$admin_perms = [];
foreach ($admins as $admin) {
    $stmt = $conn->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
    $stmt->execute([$admin['id']]);
    $admin_perms[$admin['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header mb-4">
                <h2 class="mb-1"><i class="fas fa-user-cog me-2"></i>Admin Permissions</h2>
                <p class="text-muted mb-0">Manage what each admin can access</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (empty($admins)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No admins found.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($admins as $admin): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($admin['username']); ?>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <h6 class="text-muted small"><i class="fas fa-bullhorn me-1"></i>Campaigns</h6>
                                            <?php foreach (['campaigns_view', 'campaigns_create', 'campaigns_edit', 'campaigns_delete'] as $perm): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_perms[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>"><?php echo $available_permissions[$perm]; ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h6 class="text-muted small"><i class="fas fa-users me-1"></i>Publishers</h6>
                                            <?php foreach (['publishers_view', 'publishers_create', 'publishers_edit', 'publishers_delete'] as $perm): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_perms[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>"><?php echo $available_permissions[$perm]; ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h6 class="text-muted small"><i class="fas fa-ad me-1"></i>Advertisers</h6>
                                            <?php foreach (['advertisers_view', 'advertisers_create', 'advertisers_edit', 'advertisers_delete'] as $perm): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_perms[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>"><?php echo $available_permissions[$perm]; ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h6 class="text-muted small"><i class="fas fa-chart-bar me-1"></i>Stats & Reports</h6>
                                            <?php foreach (['stats_view', 'publishers_stats_view', 'reports_view', 'reports_export'] as $perm): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="<?php echo $admin['id'] . '_' . $perm; ?>" <?php echo in_array($perm, $admin_perms[$admin['id']] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="<?php echo $admin['id'] . '_' . $perm; ?>"><?php echo $available_permissions[$perm]; ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($is_super_admin || in_array('admin_permissions_edit', $admin_permissions_list)): ?>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
