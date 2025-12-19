<?php
// admin/create_campaign.php - Add New Campaign (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Create Campaign';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('campaigns_create', $admin_permissions)) {
    header('Location: dashboard.php');
    exit();
}

// Generate a unique shortcode (auto-increment starting from 1)
function generateShortcode($conn) {
    $stmt = $conn->prepare("SELECT MAX(CAST(shortcode AS UNSIGNED)) as max_code FROM campaigns WHERE shortcode REGEXP '^[0-9]+$'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_code = ($result && $result['max_code']) ? $result['max_code'] + 1 : 1;
    return (string)$next_code;
}

// Generate unique pixel code
function generatePixelCode($length = 8) {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
}

// Initialize variables
$campaign_name = '';
$target_url = '';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));
$advertiser_payout = '';
$publisher_payout = '';
$campaign_type = 'None';
$pixel_code = '';
$advertiser_ids = [];
$publisher_ids = [];
$error = '';
$success = '';

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name = trim($_POST['campaign_name'] ?? '');
    $target_url = trim($_POST['target_url'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $advertiser_payout = !empty($_POST['advertiser_payout']) ? $_POST['advertiser_payout'] : '0';
    $publisher_payout = !empty($_POST['publisher_payout']) ? $_POST['publisher_payout'] : '0';
    $campaign_type = $_POST['campaign_type'] ?? 'None';
    $pixel_code = trim($_POST['pixel_code'] ?? '');
    $advertiser_ids = $_POST['advertiser_ids'] ?? [];
    $publisher_ids = $_POST['publisher_ids'] ?? [];
    
    if (empty($campaign_name)) {
        $error = 'Campaign name is required.';
    } elseif (empty($target_url)) {
        $error = 'Website URL is required.';
    } elseif (empty($advertiser_ids)) {
        $error = 'At least one advertiser must be selected.';
    } elseif (empty($publisher_ids)) {
        $error = 'At least one publisher must be selected.';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $error = 'End date must be after start date.';
    } else {
        try {
            $base_shortcode = generateShortcode($conn);
            $conn->beginTransaction();
            
            // Generate pixel code if not provided
            if (empty($pixel_code)) {
                $pixel_code = generatePixelCode();
            }
            
            // Insert campaign (check if pixel_code column exists)
            try {
                $stmt = $conn->prepare("
                    INSERT INTO campaigns (name, shortcode, pixel_code, target_url, start_date, end_date, 
                        advertiser_payout, publisher_payout, campaign_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$campaign_name, $base_shortcode, $pixel_code, $target_url, $start_date, $end_date,
                    $advertiser_payout, $publisher_payout, $campaign_type]);
            } catch (PDOException $e) {
                // If pixel_code column doesn't exist, insert without it
                $stmt = $conn->prepare("
                    INSERT INTO campaigns (name, shortcode, target_url, start_date, end_date, 
                        advertiser_payout, publisher_payout, campaign_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$campaign_name, $base_shortcode, $target_url, $start_date, $end_date,
                    $advertiser_payout, $publisher_payout, $campaign_type]);
                $pixel_code = '';
            }
            
            $campaign_id = $conn->lastInsertId();
            
            // Assign advertisers
            $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
            foreach ($advertiser_ids as $advertiser_id) {
                $stmt->execute([$campaign_id, $advertiser_id]);
            }
            
            // Assign publishers and generate tracking links + pixel codes
            $publisher_stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
            $shortcode_stmt = $conn->prepare("INSERT INTO publisher_short_codes (campaign_id, publisher_id, short_code) VALUES (?, ?, ?)");
            
            // Check if publisher_pixel_codes table exists
            $pixelTableCheck = $conn->query("SHOW TABLES LIKE 'publisher_pixel_codes'");
            $hasPixelTable = $pixelTableCheck->rowCount() > 0;
            
            if ($hasPixelTable) {
                $pixel_stmt = $conn->prepare("INSERT INTO publisher_pixel_codes (campaign_id, publisher_id, pixel_code) VALUES (?, ?, ?)");
            }
            
            foreach ($publisher_ids as $publisher_id) {
                $publisher_stmt->execute([$campaign_id, $publisher_id]);
                $shortcode_stmt->execute([$campaign_id, $publisher_id, $base_shortcode]);
                
                if ($hasPixelTable) {
                    $publisher_pixel_code = generatePixelCode() . '_P' . $publisher_id;
                    $pixel_stmt->execute([$campaign_id, $publisher_id, $publisher_pixel_code]);
                }
            }
            
            $conn->commit();
            
            // Build success message
            $success = "Campaign created successfully! Shortcode: $base_shortcode";
            if (!empty($pixel_code)) {
                $success .= ", Campaign Pixel: $pixel_code";
            }
            
            // Get publisher pixel codes if table exists
            if ($hasPixelTable && !empty($publisher_ids)) {
                $stmt = $conn->prepare("
                    SELECT ppc.pixel_code, p.name as publisher_name 
                    FROM publisher_pixel_codes ppc 
                    JOIN publishers p ON ppc.publisher_id = p.id 
                    WHERE ppc.campaign_id = ?
                ");
                $stmt->execute([$campaign_id]);
                $pubPixels = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($pubPixels)) {
                    $success .= "<br><br><strong>Publisher Pixel Codes:</strong><ul>";
                    foreach ($pubPixels as $pp) {
                        $success .= "<li><strong>" . htmlspecialchars($pp['publisher_name']) . ":</strong> " . $pp['pixel_code'] . "</li>";
                    }
                    $success .= "</ul>";
                }
            }
            
            $_SESSION['success_message'] = $success;
            header('Location: create_campaign.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error creating campaign: " . $e->getMessage();
        }
    }
}

// Get advertisers and publishers
$stmt = $conn->prepare("SELECT id, name FROM advertisers ORDER BY name");
$stmt->execute();
$advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id, name FROM publishers ORDER BY name");
$stmt->execute();
$publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-plus-circle me-2"></i>Add New Campaign</h2>
                    <p class="text-muted mb-0">Create a new advertising campaign</p>
                </div>
                <a href="manage_campaigns.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Campaigns
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-edit me-2"></i>Campaign Details
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="campaign_name" class="form-label">
                                <i class="fas fa-tag me-1"></i>Campaign Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="campaign_name" name="campaign_name" value="<?php echo htmlspecialchars($campaign_name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="target_url" class="form-label">
                                <i class="fas fa-link me-1"></i>Website URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" class="form-control" id="target_url" name="target_url" value="<?php echo htmlspecialchars($target_url); ?>" placeholder="https://example.com" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>End Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="advertiser_payout" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Advertiser Payout ($)
                                </label>
                                <input type="number" class="form-control" id="advertiser_payout" name="advertiser_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($advertiser_payout); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="publisher_payout" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Publisher Payout ($)
                                </label>
                                <input type="number" class="form-control" id="publisher_payout" name="publisher_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($publisher_payout); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="campaign_type" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>Campaign Type
                                </label>
                                <select class="form-select" id="campaign_type" name="campaign_type">
                                    <option value="None" <?php echo $campaign_type === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="CPR" <?php echo $campaign_type === 'CPR' ? 'selected' : ''; ?>>CPR (Cost Per Registration)</option>
                                    <option value="CPL" <?php echo $campaign_type === 'CPL' ? 'selected' : ''; ?>>CPL (Cost Per Lead)</option>
                                    <option value="CPC" <?php echo $campaign_type === 'CPC' ? 'selected' : ''; ?>>CPC (Cost Per Click)</option>
                                    <option value="CPM" <?php echo $campaign_type === 'CPM' ? 'selected' : ''; ?>>CPM (Cost Per Thousand)</option>
                                    <option value="CPS" <?php echo $campaign_type === 'CPS' ? 'selected' : ''; ?>>CPS (Cost Per Sale)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pixel_code" class="form-label">
                                    <i class="fas fa-code me-1"></i>Pixel Code (Auto-generated if empty)
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="pixel_code" name="pixel_code" value="<?php echo htmlspecialchars($pixel_code); ?>" placeholder="Leave empty to auto-generate">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateNewPixelCode()">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Used for conversion tracking</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-ad me-1"></i>Advertisers <span class="text-danger">*</span>
                                </label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background: #f8fafc;">
                                    <?php if (empty($advertisers)): ?>
                                        <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>No advertisers available. <a href="manage_advertisers.php">Add advertisers first</a>.</p>
                                    <?php else: ?>
                                        <?php foreach ($advertisers as $advertiser): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="advertiser_ids[]" value="<?php echo $advertiser['id']; ?>" id="adv_<?php echo $advertiser['id']; ?>" <?php echo in_array($advertiser['id'], $advertiser_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="adv_<?php echo $advertiser['id']; ?>"><?php echo htmlspecialchars($advertiser['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-users me-1"></i>Publishers <span class="text-danger">*</span>
                                </label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background: #f8fafc;">
                                    <?php if (empty($publishers)): ?>
                                        <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>No publishers available. <a href="manage_publishers.php">Add publishers first</a>.</p>
                                    <?php else: ?>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="<?php echo $publisher['id']; ?>" id="pub_<?php echo $publisher['id']; ?>" <?php echo in_array($publisher['id'], $publisher_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="pub_<?php echo $publisher['id']; ?>"><?php echo htmlspecialchars($publisher['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus-circle me-2"></i>Create Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateNewPixelCode() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    var code = '';
    for (var i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('pixel_code').value = code;
}
</script>

<?php require_once 'includes/footer.php'; ?>
