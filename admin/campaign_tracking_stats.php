<?php
// admin/campaign_tracking_stats.php - Campaign Tracking Statistics
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Get campaign ID from URL parameter
$campaign_id = $_GET['id'] ?? '';

if (empty($campaign_id)) {
    header('Location: manage_campaigns.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get campaign details
    $stmt = $conn->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT a.name) as advertiser_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: manage_campaigns.php');
        exit();
    }
    
    // Get publisher tracking statistics
    $stmt = $conn->prepare("
        SELECT p.name as publisher_name, 
               psc.short_code,
               COALESCE(psc.clicks, 0) as clicks
        FROM publishers p
        JOIN campaign_publishers cp ON p.id = cp.publisher_id
        JOIN publisher_short_codes psc ON cp.campaign_id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        WHERE cp.campaign_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$campaign_id]);
    $publisher_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading campaign data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Tracking Statistics - Ads Platform</title>
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
                        <a href="manage_campaigns.php" class="list-group-item list-group-item-action">Campaigns</a>
                        <a href="manage_publishers.php" class="list-group-item list-group-item-action">Manage Publishers</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Campaign: <?php echo htmlspecialchars($campaign['name']); ?></h2>
                    <a href="manage_campaigns.php" class="btn btn-secondary">Back to Campaigns</a>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Base Short Code:</strong> <?php echo htmlspecialchars($campaign['shortcode']); ?> <button class="btn btn-outline-primary btn-sm ms-2 copy-btn" onclick="copyToClipboard('http://localhost/webnetics-shorturl/<?php echo htmlspecialchars($campaign['shortcode']); ?>', this)">Copy Link</button></p>
                                <p><strong>Advertisers:</strong> <?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></p>
                                <p><strong>Start Date:</strong> <?php echo htmlspecialchars($campaign['start_date']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Campaign Type:</strong> <?php echo htmlspecialchars($campaign['campaign_type']); ?></p>
                                <p><strong>Website URL:</strong> <a href="<?php echo htmlspecialchars($campaign['target_url']); ?>" target="_blank"><?php echo htmlspecialchars($campaign['target_url']); ?></a></p>
                                <p><strong>End Date:</strong> <?php echo htmlspecialchars($campaign['end_date']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Publisher Tracking Statistics</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($publisher_stats)): ?>
                            <p>No publishers assigned to this campaign.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Publisher</th>
                                            <th>Short Code</th>
                                            <th>Tracking Link</th>
                                            <th>Clicks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_clicks = 0;
                                        foreach ($publisher_stats as $stats): 
                                            $total_clicks += $stats['clicks'];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($stats['publisher_name']); ?></td>
                                                <td><?php echo htmlspecialchars($stats['short_code']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <code id="tracking-link-<?php echo $stats['short_code']; ?>">http://localhost/webnetics-shorturl/c/<?php echo htmlspecialchars($stats['short_code']); ?></code>
                                                        <button class="btn btn-outline-primary btn-sm ms-2 copy-btn" data-link="http://localhost/webnetics-shorturl/c/<?php echo htmlspecialchars($stats['short_code']); ?>" onclick="copyToClipboard('http://localhost/webnetics-shorturl/c/<?php echo htmlspecialchars($stats['short_code']); ?>', this)">Copy</button>
                                                    </div>
                                                </td>
                                                <td><?php echo $stats['clicks']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-info">
                                            <td colspan="3"><strong>Total Clicks</strong></td>
                                            <td><strong><?php echo $total_clicks; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text, button) {
            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-1000px';
            tempInput.value = text;
            document.body.appendChild(tempInput);
            
            // Select and copy the text
            tempInput.select();
            document.execCommand('copy');
            
            // Remove the temporary input
            document.body.removeChild(tempInput);
            
            // Change button text to indicate success
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2000);
        }
    </script>
</body>
</html>