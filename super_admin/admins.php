<?php
// super_admin/admins.php - Admin User Management
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Admins';
$success = '';
$error = '';

// Available permissions - grouped by category
$available_permissions = [
    'campaigns' => [
        'campaigns_view' => 'View Campaigns',
        'campaigns_create' => 'Create Campaigns',
        'campaigns_edit' => 'Edit Campaigns',
        'campaigns_delete' => 'Delete Campaigns',
    ],
    'publishers' => [
        'publishers_view' => 'View Publishers',
        'publishers_create' => 'Create Publishers',
        'publishers_edit' => 'Edit Publishers',
        'publishers_delete' => 'Delete Publishers',
    ],
    'advertisers' => [
        'advertisers_view' => 'View Advertisers',
        'advertisers_create' => 'Create Advertisers',
        'advertisers_edit' => 'Edit Advertisers',
        'advertisers_delete' => 'Delete Advertisers',
    ],
    'admins' => [
        'admins_view' => 'View Admins',
        'admins_create' => 'Create Admins',
        'admins_edit' => 'Edit Admins',
        'admins_delete' => 'Delete Admins',
    ],
    'assignments' => [
        'advertiser_campaigns_view' => 'View Advertiser Campaigns',
        'advertiser_campaigns_edit' => 'Edit Advertiser Campaigns',
        'publisher_campaigns_view' => 'View Publisher Campaigns',
        'publisher_campaigns_edit' => 'Edit Publisher Campaigns',
    ],
    'reports' => [
        'stats_view' => 'View Statistics',
        'publishers_stats_view' => 'View Publishers Stats',
        'reports_view' => 'View Reports',
        'reports_export' => 'Export Reports',
    ],
    'system' => [
        'security_view' => 'View Security',
        'security_edit' => 'Edit Security',
        'db_backup_view' => 'View DB Backup',
        'db_backup_create' => 'Create DB Backup',
        'admin_permissions_view' => 'View Admin Permissions',
        'admin_permissions_edit' => 'Edit Admin Permissions',
    ],
];

// Handle add/edit admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $username = $_POST['username'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $password = $_POST['password'] ?? '';
        
        if (isset($_POST['edit_id']) && $_POST['edit_id']) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $role, $hashed, $_POST['edit_id']]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $role, $_POST['edit_id']]);
            }
            $success = "Admin updated successfully!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed, $role]);
            $success = "Admin added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Prevent deleting yourself
        if ($_GET['delete'] == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'super_admin')");
            $stmt->execute([$_GET['delete']]);
            $success = "Admin deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error deleting admin: " . $e->getMessage();
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE role IN ('admin', 'super_admin') ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get permissions for each admin
    $admin_permissions = [];
    foreach ($admins as $admin) {
        if ($admin['role'] === 'admin') {
            $stmt2 = $conn->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
            $stmt2->execute([$admin['id']]);
            $admin_permissions[$admin['id']] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    
} catch (PDOException $e) {
    $error = "Error loading admins: " . $e->getMessage();
    $admins = [];
    $admin_permissions = [];
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
                    <h2><i class="fas fa-user-shield me-2"></i>Admins</h2>
                    <p>Manage admin users</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Add Admin
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?> -->
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning text-white edit-btn" 
                                                data-id="<?php echo $admin['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                data-role="<?php echo $admin['role']; ?>"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $admin['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this admin?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($admin['role'] === 'admin'): ?>
                                        <button class="btn btn-sm btn-info perm-btn" 
                                                data-id="<?php echo $admin['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                data-permissions="<?php echo htmlspecialchars(json_encode($admin_permissions[$admin['id']] ?? [])); ?>"
                                                title="Manage Permissions">
                                            <i class="fas fa-user-cog"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="admin_permissions.php">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-user-cog me-2"></i>Permissions: <span id="permAdminName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="permAdminId">
                    <input type="hidden" name="redirect_to" value="admins.php">
                    
                    <div class="row">
                        <!-- Campaigns -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light"><i class="fas fa-bullhorn me-2 text-primary"></i>Campaigns</div>
                                <div class="card-body py-2">
                                    <?php foreach ($available_permissions['campaigns'] as $perm => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                        <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Publishers -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light"><i class="fas fa-users me-2 text-success"></i>Publishers</div>
                                <div class="card-body py-2">
                                    <?php foreach ($available_permissions['publishers'] as $perm => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                        <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advertisers -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light"><i class="fas fa-ad me-2 text-warning"></i>Advertisers</div>
                                <div class="card-body py-2">
                                    <?php foreach ($available_permissions['advertisers'] as $perm => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                        <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Admins -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light"><i class="fas fa-user-shield me-2 text-danger"></i>Admins</div>
                                <div class="card-body py-2">
                                    <?php foreach ($available_permissions['admins'] as $perm => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                        <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campaign Assignments -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light"><i class="fas fa-link me-2 text-info"></i>Campaign Assignments</div>
                                <div class="card-body py-2">
                                    <?php foreach ($available_permissions['assignments'] as $perm => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                        <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reports & Stats -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header py-2 bg-light"><i class="fas fa-chart-bar me-2 text-purple"></i>Reports & Stats</div>
                                <div class="card-body py-2">
                                    <?php foreach ($available_permissions['reports'] as $perm => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                        <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Settings -->
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-header py-2 bg-light"><i class="fas fa-cog me-2 text-secondary"></i>System Settings</div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        <?php foreach ($available_permissions['system'] as $perm => $label): ?>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="perm_<?php echo $perm; ?>">
                                                <label class="form-check-label small" for="perm_<?php echo $perm; ?>"><?php echo $label; ?></label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllPerms()"><i class="fas fa-check-double me-1"></i>Select All</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllPerms()"><i class="fas fa-times me-1"></i>Clear All</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save me-1"></i>Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="editId">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="inputUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="inputRole" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span id="pwdNote">(required)</span></label>
                        <input type="password" name="password" id="inputPassword" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = "
    // Edit Admin Modal
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Edit Admin';
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('inputUsername').value = this.dataset.username;
            document.getElementById('inputRole').value = this.dataset.role;
            document.getElementById('inputPassword').required = false;
            document.getElementById('pwdNote').textContent = '(leave blank to keep current)';
            new bootstrap.Modal(document.getElementById('addModal')).show();
        });
    });
    
    document.getElementById('addModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Add Admin';
        document.getElementById('editId').value = '';
        document.getElementById('inputPassword').required = true;
        document.getElementById('pwdNote').textContent = '(required)';
        this.querySelector('form').reset();
    });
    
    // Permissions Modal
    document.querySelectorAll('.perm-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            var adminId = this.dataset.id;
            var username = this.dataset.username;
            var permissions = JSON.parse(this.dataset.permissions || '[]');
            
            document.getElementById('permAdminId').value = adminId;
            document.getElementById('permAdminName').textContent = username;
            
            // Clear all checkboxes first
            document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
            
            // Check the ones that are enabled
            permissions.forEach(perm => {
                var cb = document.getElementById('perm_' + perm);
                if (cb) cb.checked = true;
            });
            
            new bootstrap.Modal(document.getElementById('permModal')).show();
        });
    });
";
require_once 'includes/footer.php';
?>

<script>
function selectAllPerms() {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = true);
}

function clearAllPerms() {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
}
</script>