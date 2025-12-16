<?php
// publisher_dashboard.php - Publisher Dashboard
session_start();

// Check if user is logged in and is a publisher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'publisher') {
    header('Location: publisher_login.php');
    exit();
}

require_once 'db_connection.php';

// Detect environment and set base URL
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false);
$base_url = $is_localhost ? 'http://' . $_SERVER['HTTP_HOST'] . '/webnetics-shorturl/' : 'https://tracking.webneticads.com/';

// Date Filter Logic
$filter_type = $_GET['filter'] ?? 'custom';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'previous_month':
            $start_date = date('Y-m-01', strtotime('first day of last month'));
            $end_date = date('Y-m-t', strtotime('last day of last month'));
            break;
        default:
            // Custom range, keep existing start/end
            break;
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get campaigns assigned to this specific publisher with their tracking links
    $stmt = $conn->prepare("
        SELECT c.id, c.name as campaign_name, p.name as publisher_name, c.start_date, c.end_date, 
               c.campaign_type, psc.short_code, COALESCE(psc.clicks, 0) as clicks, c.status
        FROM campaigns c
        JOIN campaign_publishers cp ON c.id = cp.campaign_id
        JOIN publisher_short_codes psc ON c.id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        JOIN publishers p ON cp.publisher_id = p.id
        WHERE cp.publisher_id = ? AND c.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $assigned_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get daily click data for the selected date range
    $stmt = $conn->prepare("
        SELECT 
            c.name as campaign_name,
            pdc.click_date,
            pdc.clicks
        FROM publisher_daily_clicks pdc
        JOIN campaigns c ON pdc.campaign_id = c.id
        WHERE pdc.publisher_id = ? 
        AND pdc.click_date BETWEEN ? AND ?
        ORDER BY pdc.click_date DESC, c.name ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $start_date, $end_date]);
    $daily_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stmt = $conn->prepare("
        SELECT 
            SUM(pdc.clicks) as total_clicks,
            COUNT(DISTINCT pdc.click_date) as active_days
        FROM publisher_daily_clicks pdc
        WHERE pdc.publisher_id = ? 
        AND pdc.click_date BETWEEN ? AND ?
    ");
    $stmt->execute([$_SESSION['user_id'], $start_date, $end_date]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_period_clicks = $stats['total_clicks'] ?? 0;
    $active_days = $stats['active_days'] ?? 0;
    $avg_per_day = $active_days > 0 ? $total_period_clicks / $active_days : 0;
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Dashboard - Ads Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-btn {
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        .filter-btn.active {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
        }
        .modern-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        .icon-box {
            font-size: 1.5rem;
        }
        .table-responsive {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <!-- Navigation Buttons -->
        <div class="mb-4 d-flex gap-3 justify-content-center">
            <a href="#daily-reports" class="btn btn-lg btn-primary shadow-sm">
                <i class="fas fa-chart-bar me-2"></i>Daily Click Reports
            </a>
            <a href="#assigned-campaigns" class="btn btn-lg btn-outline-primary shadow-sm">
                <i class="fas fa-list me-2"></i>Your Assigned Campaigns
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Daily Click Reports Section -->
        <div class="mb-4" id="daily-reports">
            <h2 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Daily Click Reports</h2>
            
            <!-- Date Filters -->
            <div class="modern-card mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-end g-3">
                        <div class="col-lg-5">
                            <label class="stat-label mb-2">Quick Filters</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="?filter=today" class="btn btn-outline-primary filter-btn <?php echo ($filter_type == 'today') ? 'active' : ''; ?>">Today</a>
                                <a href="?filter=yesterday" class="btn btn-outline-primary filter-btn <?php echo ($filter_type == 'yesterday') ? 'active' : ''; ?>">Yesterday</a>
                                <a href="?filter=this_month" class="btn btn-outline-primary filter-btn <?php echo ($filter_type == 'this_month') ? 'active' : ''; ?>">This Month</a>
                                <a href="?filter=previous_month" class="btn btn-outline-primary filter-btn <?php echo ($filter_type == 'previous_month') ? 'active' : ''; ?>">Previous Month</a>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="filter" value="custom">
                                <div class="col-md-5">
                                    <label class="stat-label mb-2">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="stat-label mb-2">End Date</label>
                                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="d-none d-md-block mb-2">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Apply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label mb-2">Total Clicks</div>
                                    <div class="stat-value text-primary"><?php echo number_format($total_period_clicks); ?></div>
                                </div>
                                <div class="icon-box text-primary">
                                    <i class="fas fa-mouse-pointer"></i>
                                </div>
                            </div>
                            <div class="mt-3 text-muted small">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label mb-2">Active Days</div>
                                    <div class="stat-value text-success"><?php echo $active_days; ?></div>
                                </div>
                                <div class="icon-box text-success">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                            <div class="mt-3 text-muted small">
                                <i class="fas fa-check-circle me-1"></i>Days with clicks
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label mb-2">Average/Day</div>
                                    <div class="stat-value text-warning"><?php echo number_format($avg_per_day, 1); ?></div>
                                </div>
                                <div class="icon-box text-warning">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="mt-3 text-muted small">
                                <i class="fas fa-star me-1"></i>Clicks per day
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Breakdown Table -->
            <div class="modern-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Daily Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($daily_clicks)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <h5>No click data available</h5>
                            <p>No clicks recorded for the selected period.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-bullhorn me-2"></i>Campaign Name</th>
                                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                                        <th class="text-end"><i class="fas fa-mouse-pointer me-2"></i>Clicks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daily_clicks as $click): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars($click['campaign_name']); ?></td>
                                            <td class="text-muted"><?php echo date('M d, Y', strtotime($click['click_date'])); ?></td>
                                            <td class="text-end fw-bold text-primary"><?php echo number_format($click['clicks']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="fw-bold">Total</td>
                                        <td class="text-end fw-bold text-primary"><?php echo number_format($total_period_clicks); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Assigned Campaigns Section -->
        <div class="mb-5" id="assigned-campaigns">
            <h2 class="mb-3"><i class="fas fa-list me-2"></i>Your Assigned Campaigns</h2>
        
            <?php if (empty($assigned_campaigns)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You have no campaigns assigned yet.
                </div>
            <?php else: ?>
                <div class="modern-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign Name</th>
                                        <th>Publisher</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Type</th>
                                        <th>Tracking Link</th>
                                        <th>Total Clicks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_campaigns as $campaign): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['publisher_name']); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <code class="small"><?php echo $base_url . 'c/' . htmlspecialchars($campaign['short_code']) . '/p' . $_SESSION['user_id']; ?></code>
                                                    <button class="btn btn-outline-primary btn-sm ms-2 copy-btn" onclick="copyToClipboard('<?php echo $base_url . 'c/' . htmlspecialchars($campaign['short_code']) . '/p' . $_SESSION['user_id']; ?>', this)">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="fw-bold text-primary"><?php echo number_format($campaign['clicks']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($campaign['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
            
            // Visual feedback
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2000);
        }
    </script>
</body>
</html>