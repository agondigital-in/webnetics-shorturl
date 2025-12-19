<?php
// admin/manage_admins.php - Manage Admins (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Manage Admins';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('admins_view', $admin_permissions)) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!$is_super_admin && !in_array('admins_create', $admin_permissions)) {
        $error = 'You do not have permission to create admins.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        
        // Non-super admins can only create regular admins
        if (!$is_super_admin && $role === 'super_admin') {
            $role = 'admin';
        }
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "An admin with this username already exists.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $hashedPassword, $role]);
                    $_SESSION['success_message'] = "Admin user created successfully.";
                    header('Location: manage_admins.php');
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error creating admin: " . $e->getMessage();
            }
        }
    }
}

// Handle admin deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!$is_super_admin && !in_array('admins_delete', $admin_permissions)) {
        $error = 'You do not have permission to delete admins.';
    } else {
        $user_id = $_POST['user_id'] ?? '';
        
        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } elseif (!empty($user_id)) {
            try {
                // Non-super admins cannot delete super admins
                if (!$is_super_admin) {
                    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $target_role = $stmt->fetchColumn();
                    if ($target_role === 'super_admin') {
                        $error = "You cannot delete a super admin.";
                    }
                }
                
                if (empty($error)) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'super_admin')");
                    $stmt->execute([$user_id]);
                    $_SESSION['success_message'] = "Admin user deleted successfully.";
                    header('Location: manage_admins.php');
                    exit();
                }
            } catch (PDOException $e) {
                $error = "Error deleting admin: " . $e->getMessage();
            }
        }
    }
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get all admin users
$stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE role IN ('admin', 'super_admin') ORDER BY username");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-user-shield me-2"></i>Manage Admins</h2>
                    <p class="text-muted mb-0">View and manage admin users</p>
                </div>
                <?php if ($is_super_admin || in_array('admins_create', $admin_permissions)): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                    <i class="fas fa-plus me-2"></i>Add Admin
                </button>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?> -->
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>All Admins</span>
                    <span class="badge bg-primary"><?php echo count($admins); ?> admins</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($admins)): ?>
                        <div class="p-4 text-center text-muted">No admin users found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon me-2" style="width:35px;height:35px;border-radius:8px;background:linear-gradient(135deg,<?php echo $admin['role'] === 'super_admin' ? '#ef4444,#dc2626' : '#4f46e5,#6366f1'; ?>);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;">
                                                    <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                                </div>
                                                <span class="fw-medium"><?php echo htmlspecialchars($admin['username']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($admin['role'] === 'super_admin'): ?>
                                                <span class="badge bg-danger">Super Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                        <td class="text-center">
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                                <?php if ($is_super_admin || (in_array('admins_delete', $admin_permissions) && $admin['role'] !== 'super_admin')): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this admin?')">
                                                    <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-info">You</span>
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

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="admin">Admin</option>
                            <?php if ($is_super_admin): ?>
                            <option value="super_admin">Super Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
