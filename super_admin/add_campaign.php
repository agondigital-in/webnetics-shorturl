<?php
// super_admin/add_campaign.php - Add New Campaign
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Generate a unique shortcode (auto-increment starting from 1)
function generateShortcode($conn) {
    // Get the highest existing shortcode
    $stmt = $conn->prepare("SELECT MAX(CAST(shortcode AS UNSIGNED)) as max_code FROM campaigns WHERE shortcode REGEXP '^[0-9]+$'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $next_code = ($result && $result['max_code']) ? $result['max_code'] + 1 : 1;
    
    return (string)$next_code;
}

// Generate a unique publisher shortcode
function generatePublisherShortcode($baseCode, $publisherId, $length = 4) {
    return $baseCode . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
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
    
    // Validate required fields
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
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Generate unique base shortcode (auto-increment)
            $base_shortcode = generateShortcode($conn);
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Generate pixel code if not provided
            if (empty($pixel_code)) {
                $pixel_code = generatePixelCode();
            }
            
            // Insert campaign (check if pixel_code column exists)
            try {
                $stmt = $conn->prepare("
                    INSERT INTO campaigns (
                        name, shortcode, pixel_code, target_url, start_date, end_date, 
                        advertiser_payout, publisher_payout, campaign_type
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $campaign_name, $base_shortcode, $pixel_code, $target_url, $start_date, $end_date,
                    $advertiser_payout, $publisher_payout, $campaign_type
                ]);
            } catch (PDOException $e) {
                // If pixel_code column doesn't exist, insert without it
                $stmt = $conn->prepare("
                    INSERT INTO campaigns (
                        name, shortcode, target_url, start_date, end_date, 
                        advertiser_payout, publisher_payout, campaign_type
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $campaign_name, $base_shortcode, $target_url, $start_date, $end_date,
                    $advertiser_payout, $publisher_payout, $campaign_type
                ]);
                $pixel_code = ''; // Reset since column doesn't exist
            }
            
            $campaign_id = $conn->lastInsertId();
            
            // Assign advertisers to campaign
            if (!empty($advertiser_ids)) {
                $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
                foreach ($advertiser_ids as $advertiser_id) {
                    $stmt->execute([$campaign_id, $advertiser_id]);
                }
            }
            
            // Assign publishers to campaign and generate tracking links
            if (!empty($publisher_ids)) {
                $publisher_stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
                $shortcode_stmt = $conn->prepare("INSERT INTO publisher_short_codes (campaign_id, publisher_id, short_code) VALUES (?, ?, ?)");
                
                foreach ($publisher_ids as $publisher_id) {
                    // Insert into campaign_publishers
                    $publisher_stmt->execute([$campaign_id, $publisher_id]);
                    
                    // Use the same base shortcode for all publishers
                    $shortcode_stmt->execute([$campaign_id, $publisher_id, $base_shortcode]);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = "Campaign created successfully! Shortcode: $base_shortcode" . (!empty($pixel_code) ? ", Pixel Code: $pixel_code" : "");
            
            // Reset form values
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
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error creating campaign: " . $e->getMessage();
        }
    }
}

// Get advertisers and publishers for dropdowns
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, name FROM advertisers ORDER BY name");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT id, name FROM publishers ORDER BY name");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Campaign - Ads Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9;
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }
        
        /* Sidebar - Sticky */
        .sidebar-wrapper {
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .sidebar-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .sidebar-card .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .list-group-item {
            border: none;
            border-radius: 8px !important;
            margin: 0.15rem 0;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            font-weight: 500;
            color: var(--gray);
        }
        
        .list-group-item i {
            width: 20px;
            text-align: center;
        }
        
        .list-group-item:hover {
            background: #f1f5f9;
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .list-group-item.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: transparent;
        }
        
        /* Main Content */
        .main-content {
            padding: 0;
        }
        
        /* Cards */
        .modern-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .modern-card .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        /* Page Header */
        .page-header h2 {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        /* Forms */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.625rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
        }
        
        /* Badge */
        .badge {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="fas fa-user-shield me-1"></i>
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Super Admin)
                </span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <div class="row">
            <div class="col-lg-2 col-md-3">
                <div class="sidebar-wrapper">
                    <div class="sidebar-card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0"><i class="fas fa-bars me-2"></i>Navigation</h5>
                    </div>
                    <div class="list-group list-group-flush p-3">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                        <a href="campaigns.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-bullhorn me-2"></i>Campaigns
                        </a>
                        <a href="advertisers.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i>Advertisers
                        </a>
                        <a href="publishers.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-share-alt me-2"></i>Publishers
                        </a>
                        <a href="admins.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-shield me-2"></i>Admins
                        </a>
                        <a href="advertiser_campaigns.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-ad me-2"></i>Advertiser Campaigns
                        </a>
                        <a href="publisher_campaigns.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-link me-2"></i>Publisher Campaigns
                        </a>
                        <a href="all_publishers_daily_clicks.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i>Publishers Stats
                        </a>
                        <a href="payment_reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Reports
                        </a>
                        <a href="security_settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-lock me-2"></i>Security
                        </a>
                    </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-10 col-md-9">
                <div class="page-header">
                    <h2><i class="fas fa-plus-circle me-2"></i>Add New Campaign</h2>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="modern-card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Campaign Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="campaign_name" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Campaign Name *
                                </label>
                                <input type="text" class="form-control" id="campaign_name" name="campaign_name" value="<?php echo htmlspecialchars($campaign_name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="target_url" class="form-label">
                                    <i class="fas fa-link me-1"></i>Website URL *
                                </label>
                                <input type="url" class="form-control" id="target_url" name="target_url" value="<?php echo htmlspecialchars($target_url); ?>" placeholder="https://example.com" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">
                                        <i class="fas fa-calendar-alt me-1"></i>Start Date *
                                    </label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">
                                        <i class="fas fa-calendar-check me-1"></i>End Date *
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
                                        <option value="CPM" <?php echo $campaign_type === 'CPM' ? 'selected' : ''; ?>>CPM (Cost Per Thousand Impressions)</option>
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
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-ad me-1"></i>Advertisers (Required) *
                                </label>
                                <div class="border p-3 rounded" style="background-color: #f8f9fa;">
                                    <?php if (empty($advertisers)): ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            No advertisers available. <a href="advertisers.php">Add advertisers first</a>.
                                        </p>
                                    <?php else: ?>
                                        <?php foreach ($advertisers as $advertiser): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="advertiser_ids[]" value="<?php echo $advertiser['id']; ?>" id="advertiser_<?php echo $advertiser['id']; ?>" <?php echo in_array($advertiser['id'], $advertiser_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="advertiser_<?php echo $advertiser['id']; ?>">
                                                    <?php echo htmlspecialchars($advertiser['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-users me-1"></i>Publishers (Required) *
                                </label>
                                <div class="border p-3 rounded" style="background-color: #f8f9fa;">
                                    <?php if (empty($publishers)): ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            No publishers available. <a href="publishers.php">Add publishers first</a>.
                                        </p>
                                    <?php else: ?>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="<?php echo $publisher['id']; ?>" id="publisher_<?php echo $publisher['id']; ?>" <?php echo in_array($publisher['id'], $publisher_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="publisher_<?php echo $publisher['id']; ?>">
                                                    <?php echo htmlspecialchars($publisher['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>