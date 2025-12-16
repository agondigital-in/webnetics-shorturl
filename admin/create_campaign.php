<?php
// admin/create_campaign.php - Create new campaign
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Generate a unique shortcode
function generateShortcode($length = 8) {
    return 'CAMP' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Generate a unique publisher shortcode
function generatePublisherShortcode($baseCode, $publisherId, $length = 4) {
    return $baseCode . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Initialize variables
$campaign_name = '';
$target_url = '';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));
$advertiser_payout = '';
$publisher_payout = '';
$campaign_type = 'None';
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
    $advertiser_payout = $_POST['advertiser_payout'] ?? '0';
    $publisher_payout = $_POST['publisher_payout'] ?? '0';
    $campaign_type = $_POST['campaign_type'] ?? 'None';
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
            
            // Generate unique base shortcode
            $base_shortcode = '';
            $is_unique = false;
            $attempts = 0;
            
            while (!$is_unique && $attempts < 10) {
                $base_shortcode = generateShortcode();
                $stmt = $conn->prepare("SELECT COUNT(*) FROM campaigns WHERE shortcode = ?");
                $stmt->execute([$base_shortcode]);
                if ($stmt->fetchColumn() == 0) {
                    $is_unique = true;
                }
                $attempts++;
            }
            
            if (!$is_unique) {
                throw new Exception('Unable to generate unique shortcode. Please try again.');
            }
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert campaign
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
                    
                    // Generate and insert publisher-specific short code
                    $publisher_shortcode = generatePublisherShortcode($base_shortcode, $publisher_id);
                    $shortcode_stmt->execute([$campaign_id, $publisher_id, $publisher_shortcode]);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = "Campaign created successfully with base shortcode: $base_shortcode";
            
            // Reset form values
            $campaign_name = '';
            $target_url = '';
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+30 days'));
            $advertiser_payout = '';
            $publisher_payout = '';
            $campaign_type = 'None';
            $advertiser_ids = [];
            $publisher_ids = [];
            
        } catch (Exception $e) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                <a class="nav-link btn btn-outline-light" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Navigation</h5>
                    </div>
                     <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="manage_campaigns.php" class="list-group-item list-group-item-action active">Manage Campaigns</a>
                        <a href="manage_publishers.php" class="list-group-item list-group-item-action">Manage Publishers</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2>Add New Campaign</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="campaign_name" class="form-label">Campaign Name *</label>
                                    <input type="text" class="form-control" id="campaign_name" name="campaign_name" value="<?php echo htmlspecialchars($campaign_name); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="target_url" class="form-label">Target URL *</label>
                                    <input type="url" class="form-control" id="target_url" name="target_url" value="<?php echo htmlspecialchars($target_url); ?>" placeholder="https://example.com" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date *</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="advertiser_payout" class="form-label">Advertiser Payout ($)</label>
                                    <input type="number" class="form-control" id="advertiser_payout" name="advertiser_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($advertiser_payout); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="publisher_payout" class="form-label">Publisher Payout ($)</label>
                                    <input type="number" class="form-control" id="publisher_payout" name="publisher_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($publisher_payout); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="campaign_type" class="form-label">Campaign Type</label>
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
                                    <label for="target_leads" class="form-label">Target Leads</label>
                                    <input type="number" class="form-control" id="target_leads" name="target_leads" min="0" value="<?php echo htmlspecialchars($target_leads); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Advertisers (Required) *</label>
                                    <div class="border p-3 rounded">
                                        <?php if (empty($advertisers)): ?>
                                            <p class="text-muted">No advertisers available. <a href="manage_advertisers.php">Add advertisers first</a>.</p>
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
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Publishers (Required) *</label>
                                    <div class="border p-3 rounded">
                                        <?php if (empty($publishers)): ?>
                                            <p class="text-muted">No publishers available. <a href="manage_publishers.php">Add publishers first</a>.</p>
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
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Campaign</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>