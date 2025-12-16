<?php
// super_admin/security_settings.php - PIN & Password Management
session_start();

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Security Settings';
$success = '';
$error = '';
$pin_not_set = false;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if PIN is set for current user
    $stmt = $conn->prepare("SELECT security_pin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $pin_not_set = empty($user['security_pin']);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Set Initial PIN (requires current password)
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
        
        // Change Password (requires current PIN)
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
        
        // Change PIN (requires current password)
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
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

$extra_css = '
    .security-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .security-card.warning-card {
        border-left: 4px solid var(--warning);
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    }
    .security-card h4 {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }
    .card-icon.pin-icon { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
    .card-icon.password-icon { background: linear-gradient(135deg, var(--success), #059669); color: white; }
    .card-icon.warning-icon { background: linear-gradient(135deg, var(--warning), #d97706); color: white; }
    .pin-input {
        font-size: 1.5rem;
        text-align: center;
        letter-spacing: 0.5rem;
        font-weight: 700;
        max-width: 150px;
    }
    .btn-security {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border: none;
        border-radius: 8px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        color: white;
    }
    .btn-security:hover { color: white; opacity: 0.9; }
    .btn-success-custom {
        background: linear-gradient(135deg, var(--success), #059669);
    }
    .info-box {
        background: #eff6ff;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-top: 1rem;
        font-size: 0.875rem;
        color: #1e40af;
    }
';

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-lock me-2"></i>Security Settings</h2>
                <p>Manage your PIN and Password for enhanced security</p>
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
                        <!-- Set Initial PIN -->
                        <div class="security-card warning-card">
                            <div class="card-icon warning-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4>Set Your Security PIN</h4>
                            <p class="text-muted">Set a 4-digit PIN to enable dual security features.</p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="set_pin">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" style="max-width: 300px;" required>
                                </div>
                                <div class="row">
                                    <div class="col-auto mb-3">
                                        <label class="form-label fw-semibold">New 4-Digit PIN</label>
                                        <input type="password" name="new_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                    </div>
                                    <div class="col-auto mb-3">
                                        <label class="form-label fw-semibold">Confirm PIN</label>
                                        <input type="password" name="confirm_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-security"><i class="fas fa-key me-2"></i>Set Security PIN</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Change Password using PIN -->
                        <div class="security-card">
                            <div class="card-icon password-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <h4>Change Password</h4>
                            <p class="text-muted">Use your 4-digit PIN to change your password</p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current PIN</label>
                                    <input type="password" name="current_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">New Password</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-security btn-success-custom"><i class="fas fa-lock me-2"></i>Change Password</button>
                            </form>
                            <div class="info-box"><i class="fas fa-info-circle me-2"></i><strong>Tip:</strong> If you forget your password, use your PIN to reset it.</div>
                        </div>
                        
                        <!-- Change PIN using Password -->
                        <div class="security-card">
                            <div class="card-icon pin-icon">
                                <i class="fas fa-th"></i>
                            </div>
                            <h4>Change PIN</h4>
                            <p class="text-muted">Use your password to change your 4-digit PIN</p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="change_pin">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" style="max-width: 300px;" required>
                                </div>
                                <div class="row">
                                    <div class="col-auto mb-3">
                                        <label class="form-label fw-semibold">New 4-Digit PIN</label>
                                        <input type="password" name="new_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                    </div>
                                    <div class="col-auto mb-3">
                                        <label class="form-label fw-semibold">Confirm PIN</label>
                                        <input type="password" name="confirm_pin" class="form-control pin-input" maxlength="4" pattern="\d{4}" required placeholder="••••">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-security"><i class="fas fa-th me-2"></i>Change PIN</button>
                            </form>
                            <div class="info-box"><i class="fas fa-info-circle me-2"></i><strong>Tip:</strong> If you forget your PIN, use your password to reset it.</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="security-card" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7);">
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

<?php
$extra_js = "
    document.querySelectorAll('.pin-input').forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
";
require_once 'includes/footer.php';
?>
