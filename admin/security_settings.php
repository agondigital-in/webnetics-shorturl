<?php
// admin/security_settings.php - Security Settings (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Security Settings';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('security_view', $admin_permissions)) {
    header('Location: dashboard.php');
    exit();
}

$success = '';
$error = '';
$pin_not_set = false;

// Check if PIN is set
$stmt = $conn->prepare("SELECT security_pin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$pin_not_set = empty($user['security_pin']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_super_admin && !in_array('security_edit', $admin_permissions)) {
        $error = 'You do not have permission to edit security settings.';
    } else {
        // Set Initial PIN
        if (isset($_POST['action']) && $_POST['action'] === 'set_pin') {
            $current_password = $_POST['current_password'] ?? '';
            $new_pin = $_POST['new_pin'] ?? '';
            $confirm_pin = $_POST['confirm_pin'] ?? '';
            
            if (!preg_match('/^\d{4}$/', $new_pin)) {
                $error = "PIN must be exactly 4 digits!";
            } elseif ($new_pin !== $confirm_pin) {
                $error = "PINs do not match!";
            } else {
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user['password'])) {
                    $stmt = $conn->prepare("UPDATE users SET security_pin = ? WHERE id = ?");
                    $stmt->execute([$new_pin, $_SESSION['user_id']]);
                    $success = "Security PIN set successfully!";
                    $pin_not_set = false;
                } else {
                    $error = "Current password is incorrect!";
                }
            }
        }
        
        // Change Password
        if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
            $current_pin = $_POST['current_pin'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters!";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match!";
            } else {
                $stmt = $conn->prepare("SELECT security_pin FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current_pin === $user['security_pin']) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $success = "Password changed successfully!";
                } else {
                    $error = "Current PIN is incorrect!";
                }
            }
        }
        
        // Change PIN
        if (isset($_POST['action']) && $_POST['action'] === 'change_pin') {
            $current_password = $_POST['current_password'] ?? '';
            $new_pin = $_POST['new_pin'] ?? '';
            $confirm_pin = $_POST['confirm_pin'] ?? '';
            
            if (!preg_match('/^\d{4}$/', $new_pin)) {
                $error = "PIN must be exactly 4 digits!";
            } elseif ($new_pin !== $confirm_pin) {
                $error = "PINs do not match!";
            } else {
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user['password'])) {
                    $stmt = $conn->prepare("UPDATE users SET security_pin = ? WHERE id = ?");
                    $stmt->execute([$new_pin, $_SESSION['user_id']]);
                    $success = "Security PIN changed successfully!";
                } else {
                    $error = "Current password is incorrect!";
                }
            }
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<style>
    .pin-input { font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem; font-weight: 700; max-width: 150px; }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header mb-4">
                <h2 class="mb-1"><i class="fas fa-lock me-2"></i>Security Settings</h2>
                <p class="text-muted mb-0">Manage your PIN and Password</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <?php if ($pin_not_set): ?>
                        <div class="card mb-4" style="border-left: 4px solid #f59e0b;">
                            <div class="card-body">
                                <h5><i class="fas fa-exclamation-triangle text-warning me-2"></i>Set Your Security PIN</h5>
                                <p class="text-muted">Set a 4-digit PIN to enable dual security features.</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="set_pin">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" style="max-width: 300px;" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-auto mb-3">
                                            <label class="form-label">New 4-Digit PIN</label>
                                            <input type="password" name="new_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                        </div>
                                        <div class="col-auto mb-3">
                                            <label class="form-label">Confirm PIN</label>
                                            <input type="password" name="confirm_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-key me-2"></i>Set Security PIN</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Change Password -->
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-key me-2"></i>Change Password</div>
                            <div class="card-body">
                                <p class="text-muted">Use your 4-digit PIN to change your password</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label">Current PIN</label>
                                        <input type="password" name="current_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success"><i class="fas fa-lock me-2"></i>Change Password</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change PIN -->
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-th me-2"></i>Change PIN</div>
                            <div class="card-body">
                                <p class="text-muted">Use your password to change your 4-digit PIN</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_pin">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" style="max-width: 300px;" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-auto mb-3">
                                            <label class="form-label">New 4-Digit PIN</label>
                                            <input type="password" name="new_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                        </div>
                                        <div class="col-auto mb-3">
                                            <label class="form-label">Confirm PIN</label>
                                            <input type="password" name="confirm_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-th me-2"></i>Change PIN</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
                        <div class="card-body">
                            <h5><i class="fas fa-shield-alt me-2 text-success"></i>Dual Security</h5>
                            <ul class="mb-0 ps-3" style="color: #166534;">
                                <li class="mb-2"><strong>Password → PIN:</strong> Change your PIN</li>
                                <li><strong>PIN → Password:</strong> Change your password</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.pin-input').forEach(input => {
    input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
