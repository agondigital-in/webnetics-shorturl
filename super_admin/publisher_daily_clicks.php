<?php
// super_admin/publisher_daily_clicks.php - View daily click statistics
session_start();

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get campaign ID from URL
$campaign_id = $_GET['id'] ?? null;

if (!$campaign_id) {
    header('Location: campaigns.php');
    exit();
}

// Get campaign details
$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

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

// Get daily clicks data
$stmt = $conn->prepare("
    SELECT 
        pdc.click_date,
        p.name as publisher_name,
        p.id as publisher_id,
        pdc.clicks,
        pdc.created_at
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    WHERE pdc.campaign_id = ? 
    AND pdc.click_date BETWEEN ? AND ?
    ORDER BY pdc.click_date DESC, p.name ASC
");
$stmt->execute([$campaign_id, $start_date, $end_date]);
$daily_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary by publisher
$stmt = $conn->prepare("
    SELECT 
        p.name as publisher_name,
        p.id as publisher_id,
        SUM(pdc.clicks) as total_clicks,
        COUNT(DISTINCT pdc.click_date) as active_days
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    WHERE pdc.campaign_id = ? 
    AND pdc.click_date BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_clicks DESC
");
$stmt->execute([$campaign_id, $start_date, $end_date]);
$publisher_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total clicks for the period
$total_period_clicks = 0;
foreach ($publisher_summary as $p) {
    $total_period_clicks += $p['total_clicks'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Click Statistics - <?php echo htmlspecialchars($campaign['name']); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Theme -->
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top: 80px;">
        <div class="row g-4 p-4">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar-nav">
                    <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="campaigns.php" class="nav-link-custom active"><i class="fas fa-bullhorn"></i> Campaigns</a>
                    <a href="advertisers.php" class="nav-link-custom"><i class="fas fa-users"></i> Advertisers</a>
                    <a href="publishers.php" class="nav-link-custom"><i class="fas fa-network-wired"></i> Publishers</a>
                    <a href="admins.php" class="nav-link-custom"><i class="fas fa-user-shield"></i> Admins</a>
                    <a href="payment_reports.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar"></i> Reports</a>
                    <a href="all_publishers_daily_clicks.php" class="nav-link-custom"><i class="fas fa-chart-bar"></i> All Publishers Stats</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-dark">Campaign Analytics</h2>
                        <p class="text-secondary mb-0">Detailed click tracking for <span class="text-primary fw-semibold"><?php echo htmlspecialchars($campaign['name']); ?></span></p>
                    </div>
                    <a href="campaign_tracking_stats.php?id=<?php echo $campaign_id; ?>" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Back to Stats
                    </a>
                </div>

                <!-- Filters -->
                <div class="modern-card mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-end g-3">
                            <div class="col-lg-5">
                                <label class="text-secondary mb-2 text-uppercase small fw-bold">Quick Filters</label>
                                <div class="d-flex gap-2">
                                    <a href="?id=<?php echo $campaign_id; ?>&filter=today" class="btn btn-outline-custom flex-grow-1 <?php echo ($filter_type == 'today') ? 'active' : ''; ?>">Today</a>
                                    <a href="?id=<?php echo $campaign_id; ?>&filter=yesterday" class="btn btn-outline-custom flex-grow-1 <?php echo ($filter_type == 'yesterday') ? 'active' : ''; ?>">Yesterday</a>
                                    <a href="?id=<?php echo $campaign_id; ?>&filter=this_month" class="btn btn-outline-custom flex-grow-1 <?php echo ($filter_type == 'this_month') ? 'active' : ''; ?>">Month</a>
                                    <a href="?id=<?php echo $campaign_id; ?>&filter=previous_month" class="btn btn-outline-custom flex-grow-1 <?php echo ($filter_type == 'previous_month') ? 'active' : ''; ?>">Prev Month</a>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="id" value="<?php echo $campaign_id; ?>">
                                    <input type="hidden" name="filter" value="custom">
                                    <div class="col-md-5">
                                        <label class="text-secondary mb-2 text-uppercase small fw-bold">Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="text-secondary mb-2 text-uppercase small fw-bold">End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="d-none d-md-block mb-2">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="modern-card stat-card-gradient p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label mb-2">Total Clicks</div>
                                    <div class="stat-value text-primary"><?php echo number_format($total_period_clicks); ?></div>
                                </div>
                                <div class="icon-box bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                                    <i class="fas fa-mouse-pointer fa-lg"></i>
                                </div>
                            </div>
                            <div class="mt-3 text-secondary small">
                                <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modern-card stat-card-gradient p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label mb-2">Active Publishers</div>
                                    <div class="stat-value text-success"><?php echo count($publisher_summary); ?></div>
                                </div>
                                <div class="icon-box bg-success bg-opacity-10 p-3 rounded-circle text-success">
                                    <i class="fas fa-users fa-lg"></i>
                                </div>
                            </div>
                            <div class="mt-3 text-secondary small">
                                <i class="fas fa-check-circle me-1"></i> Contributing traffic
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modern-card stat-card-gradient p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label mb-2">Top Performer</div>
                                    <div class="stat-value text-warning" style="font-size: 1.8rem;">
                                        <?php echo !empty($publisher_summary) ? htmlspecialchars($publisher_summary[0]['publisher_name']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="icon-box bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                                    <i class="fas fa-trophy fa-lg"></i>
                                </div>
                            </div>
                            <div class="mt-3 text-secondary small">
                                <i class="fas fa-star me-1"></i> Most clicks generated
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Publisher Summary -->
                    <div class="col-lg-6">
                        <div class="modern-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Publisher Performance</h5>
                                <span class="badge badge-soft-primary rounded-pill px-3 py-2">Summary</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($publisher_summary)): ?>
                                    <div class="p-4 text-center text-secondary">No data available</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Publisher</th>
                                                    <th class="text-end">Clicks</th>
                                                    <th class="text-center">Active Days</th>
                                                    <th class="text-end">Avg/Day</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($publisher_summary as $summary): ?>
                                                    <tr>
                                                        <td class="fw-medium text-dark"><?php echo htmlspecialchars($summary['publisher_name']); ?></td>
                                                        <td class="text-end fw-bold text-primary"><?php echo number_format($summary['total_clicks']); ?></td>
                                                        <td class="text-center text-secondary"><?php echo $summary['active_days']; ?></td>
                                                        <td class="text-end text-secondary"><?php echo number_format($summary['total_clicks'] / max($summary['active_days'], 1), 1); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Breakdown -->
                    <div class="col-lg-6">
                        <div class="modern-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Daily Breakdown</h5>
                                <span class="badge badge-soft-success rounded-pill px-3 py-2">Detailed</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($daily_clicks)): ?>
                                    <div class="p-4 text-center text-secondary">No daily records found</div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Publisher</th>
                                                    <th class="text-end">Clicks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($daily_clicks as $click): ?>
                                                    <tr>
                                                        <td class="text-secondary"><?php echo date('M d, Y', strtotime($click['click_date'])); ?></td>
                                                        <td class="fw-medium text-dark"><?php echo htmlspecialchars($click['publisher_name']); ?></td>
                                                        <td class="text-end fw-bold text-dark"><?php echo number_format($click['clicks']); ?></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
