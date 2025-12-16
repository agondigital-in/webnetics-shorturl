<?php
// super_admin/edit_campaign.php - Edit Campaign
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Generate a unique publisher shortcode
function generatePublisherShortcode($length = 8) {
    return 'CAMP' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$campaign_id = $_GET['id'] ?? '';
$error = '';
$success = '';

// Validate campaign ID
if (empty($campaign_id)) {
    header('Location: campaigns.php');
    exit();
}

// Fetch campaign details
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get campaign details
    $stmt = $conn->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT ca.advertiser_id) as advertiser_ids,
               GROUP_CONCAT(DISTINCT cp.publisher_id) as publisher_ids
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit();
    }
    
    // Get all advertisers and publishers
    $stmt = $conn->prepare("SELECT id, name FROM advertisers ORDER BY name");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT id, name FROM publishers ORDER BY name");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading campaign: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_url = trim($_POST['target_url'] ?? '');
    $advertiser_payout = $_POST['advertiser_payout'] ?? '0';
    $publisher_payout = $_POST['publisher_payout'] ?? '0';
    $advertiser_ids = $_POST['advertiser_ids'] ?? [];
    $publisher_ids = $_POST['publisher_ids'] ?? [];
    
    // Validate required fields
    if (empty($target_url)) {
        $error = 'Website URL is required.';
    } elseif (empty($advertiser_ids)) {
        $error = 'At least one advertiser must be selected.';
    } elseif (empty($publisher_ids)) {
        $error = 'At least one publisher must be selected.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Check if target URL has changed
            $stmt = $conn->prepare("SELECT target_url FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            $old_target_url = $stmt->fetchColumn();
            
            $url_changed = ($old_target_url !== $target_url);
            
            // Update campaign target URL and payout values
            $stmt = $conn->prepare("UPDATE campaigns SET target_url = ?, advertiser_payout = ?, publisher_payout = ? WHERE id = ?");
            $stmt->execute([$target_url, $advertiser_payout, $publisher_payout, $campaign_id]);
            
            // Click counts continue to accumulate even when URL changes
            // No action needed here as clicks are preserved
            
            // Update advertisers - first delete existing associations
            $stmt = $conn->prepare("DELETE FROM campaign_advertisers WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            
            // Then insert new associations
            if (!empty($advertiser_ids)) {
                $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
                foreach ($advertiser_ids as $advertiser_id) {
                    $stmt->execute([$campaign_id, $advertiser_id]);
                }
            }
            
            // Check if publisher assignments have changed
            $stmt = $conn->prepare("SELECT GROUP_CONCAT(publisher_id ORDER BY publisher_id) as publisher_ids FROM campaign_publishers WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            $old_publisher_ids = $stmt->fetchColumn();
            
            $new_publisher_ids = !empty($publisher_ids) ? implode(',', array_map('intval', $publisher_ids)) : '';
            sort($publisher_ids);
            $new_publisher_ids_sorted = !empty($publisher_ids) ? implode(',', $publisher_ids) : '';
            
            $publishers_changed = ($old_publisher_ids !== $new_publisher_ids_sorted);
            
            // Only update publishers if assignments have changed
            if ($publishers_changed) {
                // Get current publisher assignments
                $stmt = $conn->prepare("SELECT publisher_id FROM campaign_publishers WHERE campaign_id = ?");
                $stmt->execute([$campaign_id]);
                $current_publisher_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // Get publisher IDs to add (newly assigned)
                $publishers_to_add = array_diff($publisher_ids, $current_publisher_ids);
                
                // Get publisher IDs to remove (no longer assigned)
                $publishers_to_remove = array_diff($current_publisher_ids, $publisher_ids);
                
                // Remove publisher associations
                if (!empty($publishers_to_remove)) {
                    $placeholders = str_repeat('?,', count($publishers_to_remove) - 1) . '?';
                    $stmt = $conn->prepare("DELETE FROM campaign_publishers WHERE campaign_id = ? AND publisher_id IN ($placeholders)");
                    $params = array_merge([$campaign_id], $publishers_to_remove);
                    $stmt->execute($params);
                    
                    // Also remove publisher short codes
                    $stmt = $conn->prepare("DELETE FROM publisher_short_codes WHERE campaign_id = ? AND publisher_id IN ($placeholders)");
                    $stmt->execute($params);
                }
                
                // Add new publisher associations
                if (!empty($publishers_to_add)) {
                    $stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
                    foreach ($publishers_to_add as $publisher_id) {
                        $stmt->execute([$campaign_id, $publisher_id]);
                    }
                    
                    // Add new publisher short codes
                    $shortcode_stmt = $conn->prepare("INSERT INTO publisher_short_codes (campaign_id, publisher_id, short_code) VALUES (?, ?, ?)");
                    foreach ($publishers_to_add as $publisher_id) {
                        // Generate unique publisher-specific short code
                        $publisher_shortcode = '';
                        $is_unique = false;
                        $attempts = 0;
                        
                        while (!$is_unique && $attempts < 10) {
                            $publisher_shortcode = generatePublisherShortcode();
                            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM publisher_short_codes WHERE short_code = ?");
                            $check_stmt->execute([$publisher_shortcode]);
                            if ($check_stmt->fetchColumn() == 0) {
                                $is_unique = true;
                            }
                            $attempts++;
                        }
                        
                        if ($is_unique) {
                            $shortcode_stmt->execute([$campaign_id, $publisher_id, $publisher_shortcode]);
                        }
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            if ($url_changed && $publishers_changed) {
                $success = "Campaign updated successfully. All tracking links now point to the new website URL. Click counts have been preserved. Publisher assignments have also changed.";
            } elseif ($url_changed) {
                $success = "Campaign updated successfully. All tracking links now point to the new website URL. Click counts have been preserved.";
            } elseif ($publishers_changed) {
                $success = "Campaign updated successfully. Publisher assignments have changed. Click counts have been preserved for existing publishers.";
            } else {
                $success = "Campaign updated successfully.";
            }
            
            // Refresh campaign data
            $stmt = $conn->prepare("
                SELECT c.*, 
                       GROUP_CONCAT(DISTINCT ca.advertiser_id) as advertiser_ids,
                       GROUP_CONCAT(DISTINCT cp.publisher_id) as publisher_ids
                FROM campaigns c
                LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
                LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
                WHERE c.id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$campaign_id]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error updating campaign: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Super Admin)</span>
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
                        <a href="dashboard.php" class="list-group-item list-group-item-action">Home Dashboard</a>
                        <a href="campaigns.php" class="list-group-item list-group-item-action">Campaigns</a>
                        <a href="advertisers.php" class="list-group-item list-group-item-action">Advertisers</a>
                        <a href="publishers.php" class="list-group-item list-group-item-action">Publishers</a>
                        <a href="admins.php" class="list-group-item list-group-item-action">Admins</a>
                        <a href="advertiser_campaigns.php" class="list-group-item list-group-item-action">View Advertiser Campaigns</a>
                        <a href="publisher_campaigns.php" class="list-group-item list-group-item-action">View Publisher Campaigns</a>
                        <a href="payment_reports.php" class="list-group-item list-group-item-action">Payment Reports</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Edit Campaign: <?php echo htmlspecialchars($campaign['name']); ?></h2>
                    <a href="campaigns.php" class="btn btn-secondary">Back to Campaigns</a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="campaign_name" class="form-label">Campaign Name</label>
                                <input type="text" class="form-control" id="campaign_name" name="campaign_name" value="<?php echo htmlspecialchars($campaign['name']); ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="target_url" class="form-label">Website URL *</label>
                                <input type="url" class="form-control" id="target_url" name="target_url" value="<?php echo htmlspecialchars($campaign['target_url']); ?>" placeholder="https://example.com" required>
                                <div class="form-text">All existing tracking links will automatically redirect to this new URL. Click counts will be preserved when the URL is changed.</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($campaign['start_date']); ?>" disabled>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($campaign['end_date']); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="advertiser_payout" class="form-label">Advertiser Payout ($)</label>
                                    <input type="number" class="form-control" id="advertiser_payout" name="advertiser_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($campaign['advertiser_payout']); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="publisher_payout" class="form-label">Publisher Payout ($)</label>
                                    <input type="number" class="form-control" id="publisher_payout" name="publisher_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($campaign['publisher_payout']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="campaign_type" class="form-label">Campaign Type</label>
                                <select class="form-select" id="campaign_type" name="campaign_type" disabled>
                                    <option value="None" <?php echo $campaign['campaign_type'] === 'None' ? 'selected' : ''; ?>>None</option>
                                    <option value="CPR" <?php echo $campaign['campaign_type'] === 'CPR' ? 'selected' : ''; ?>>CPR (Cost Per Registration)</option>
                                    <option value="CPL" <?php echo $campaign['campaign_type'] === 'CPL' ? 'selected' : ''; ?>>CPL (Cost Per Lead)</option>
                                    <option value="CPC" <?php echo $campaign['campaign_type'] === 'CPC' ? 'selected' : ''; ?>>CPC (Cost Per Click)</option>
                                    <option value="CPM" <?php echo $campaign['campaign_type'] === 'CPM' ? 'selected' : ''; ?>>CPM (Cost Per Thousand Impressions)</option>
                                    <option value="CPS" <?php echo $campaign['campaign_type'] === 'CPS' ? 'selected' : ''; ?>>CPS (Cost Per Sale)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Advertisers (Required) *</label>
                                <div class="border p-3 rounded">
                                    <?php if (empty($advertisers)): ?>
                                        <p class="text-muted">No advertisers available.</p>
                                    <?php else: ?>
                                        <?php 
                                        $current_advertiser_ids = !empty($campaign['advertiser_ids']) ? explode(',', $campaign['advertiser_ids']) : [];
                                        ?>
                                        <?php foreach ($advertisers as $advertiser): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="advertiser_ids[]" value="<?php echo $advertiser['id']; ?>" id="advertiser_<?php echo $advertiser['id']; ?>" <?php echo in_array($advertiser['id'], $current_advertiser_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="advertiser_<?php echo $advertiser['id']; ?>">
                                                    <?php echo htmlspecialchars($advertiser['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Publishers (Required) *</label>
                                <div class="border p-3 rounded">
                                    <?php if (empty($publishers)): ?>
                                        <p class="text-muted">No publishers available.</p>
                                    <?php else: ?>
                                        <?php 
                                        $current_publisher_ids = !empty($campaign['publisher_ids']) ? explode(',', $campaign['publisher_ids']) : [];
                                        ?>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="<?php echo $publisher['id']; ?>" id="publisher_<?php echo $publisher['id']; ?>" <?php echo in_array($publisher['id'], $current_publisher_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="publisher_<?php echo $publisher['id']; ?>">
                                                    <?php echo htmlspecialchars($publisher['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Campaign</button>
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