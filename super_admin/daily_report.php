<?php
// daily_report.php - Super Admin Daily Report Dashboard
session_start();

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Set default date to today
$report_date = date('Y-m-d');
$date_range = isset($_GET['range']) ? $_GET['range'] : '';
$report_start_date = $report_date;
$report_end_date = $report_date;

// Handle different date range options
if ($date_range === 'today') {
    $report_date = date('Y-m-d');
    $report_start_date = $report_date;
    $report_end_date = $report_date;
} elseif ($date_range === 'yesterday') {
    $report_date = date('Y-m-d', strtotime('-1 day'));
    $report_start_date = $report_date;
    $report_end_date = $report_date;
} elseif ($date_range === 'this_month') {
    $report_date = date('Y-m-01'); // First day of current month
    $report_start_date = $report_date;
    $report_end_date = date('Y-m-t'); // Last day of current month
} elseif ($date_range === 'this_year') {
    $report_date = date('Y-01-01'); // First day of current year
    $report_start_date = $report_date;
    $report_end_date = date('Y-12-31'); // Last day of current year
} elseif (isset($_GET['date'])) {
    $report_date = $_GET['date'];
    $report_start_date = $report_date;
    $report_end_date = $report_date;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Step 2: Fetch All Campaigns
$stmt = $conn->prepare("
    SELECT id, name, shortcode, click_count 
    FROM campaigns
    ORDER BY name
");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get advertiser and publisher information for each campaign
$campaign_details = [];
foreach ($campaigns as $campaign) {
    $campaign_id = $campaign['id'];
    
    // Get advertisers for this campaign
    $stmt = $conn->prepare("
        SELECT a.name 
        FROM advertisers a
        JOIN campaign_advertisers ca ON a.id = ca.advertiser_id
        WHERE ca.campaign_id = ?
    ");
    $stmt->execute([$campaign_id]);
    $advertisers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get publishers for this campaign
    $stmt = $conn->prepare("
        SELECT p.name 
        FROM publishers p
        JOIN campaign_publishers cp ON p.id = cp.publisher_id
        WHERE cp.campaign_id = ?
    ");
    $stmt->execute([$campaign_id]);
    $publishers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $campaign_details[] = [
        'id' => $campaign['id'],
        'name' => $campaign['name'],
        'shortcode' => $campaign['shortcode'],
        'total_clicks' => $campaign['click_count'],
        'advertisers' => $advertisers,
        'publishers' => $publishers
    ];
}

// Step 3: Campaign Daily Click Summary
// Get daily click data from publisher_short_codes table which has created_at timestamp
// Also get advertiser information for each campaign
// Handle date ranges
$stmt = $conn->prepare("
    SELECT psc.campaign_id, psc.publisher_id, SUM(psc.clicks) as total_clicks, p.name as publisher_name, c.name as campaign_name,
           GROUP_CONCAT(DISTINCT a.name) as advertiser_names
    FROM publisher_short_codes psc
    JOIN publishers p ON psc.publisher_id = p.id
    JOIN campaigns c ON psc.campaign_id = c.id
    LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
    LEFT JOIN advertisers a ON ca.advertiser_id = a.id
    WHERE DATE(psc.created_at) BETWEEN ? AND ?
    GROUP BY psc.campaign_id, psc.publisher_id, p.name, c.name
");
$stmt->execute([$report_start_date, $report_end_date]);
$daily_click_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize campaign clicks by campaign and publisher
$campaign_click_summary = [];
$publisher_click_summary = [];

foreach ($daily_click_data as $click_data) {
    $campaign_id = $click_data['campaign_id'];
    $publisher_id = $click_data['publisher_id'];
    $clicks = $click_data['total_clicks']; // Now using SUM of clicks
    $campaign_name = $click_data['campaign_name'];
    $publisher_name = $click_data['publisher_name'];
    $advertiser_names = $click_data['advertiser_names'] ? explode(',', $click_data['advertiser_names']) : [];
    
    // Add to campaign summary
    if (!isset($campaign_click_summary[$campaign_id])) {
        $campaign_click_summary[$campaign_id] = [
            'name' => $campaign_name,
            'total_clicks' => 0,
            'publishers' => [],
            'advertisers' => $advertiser_names
        ];
    }
    $campaign_click_summary[$campaign_id]['total_clicks'] += $clicks;
    $campaign_click_summary[$campaign_id]['publishers'][$publisher_id] = [
        'name' => $publisher_name,
        'clicks' => $clicks,
        'advertisers' => $advertiser_names
    ];
    
    // Add to publisher summary
    if (!isset($publisher_click_summary[$publisher_id])) {
        $publisher_click_summary[$publisher_id] = [
            'name' => $publisher_name,
            'total_clicks' => 0,
            'campaigns' => []
        ];
    }
    $publisher_click_summary[$publisher_id]['total_clicks'] += $clicks;
    $publisher_click_summary[$publisher_id]['campaigns'][$campaign_id] = [
        'name' => $campaign_name,
        'clicks' => $clicks,
        'advertisers' => $advertiser_names
    ];
}

// Get publisher names for the summary
$publishers_map = [];
$stmt = $conn->prepare("SELECT id, name FROM publishers");
$stmt->execute();
$publishers_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($publishers_result as $pub) {
    $publishers_map[$pub['id']] = $pub['name'];
}

// Get campaign names for the summary
$campaigns_map = [];
$stmt = $conn->prepare("SELECT id, name FROM campaigns");
$stmt->execute();
$campaigns_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($campaigns_result as $camp) {
    $campaigns_map[$camp['id']] = $camp['name'];
}

// Step 4: Publisher Daily Summary (all clicks up to selected date)
// For publisher_short_codes, we'll use created_at since there's no updated_at
$stmt = $conn->prepare("
    SELECT publisher_id, campaign_id, SUM(clicks) as total_clicks
    FROM publisher_short_codes
    WHERE DATE(created_at) <= ?
    GROUP BY publisher_id, campaign_id
");
$stmt->execute([$report_end_date]);
$publisher_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize publisher performance data
$publisher_performance_summary = [];
foreach ($publisher_performance as $performance) {
    $publisher_id = $performance['publisher_id'];
    $campaign_id = $performance['campaign_id'];
    $clicks = $performance['total_clicks'];
    
    if (!isset($publisher_performance_summary[$publisher_id])) {
        $publisher_performance_summary[$publisher_id] = [
            'name' => $publishers_map[$publisher_id] ?? 'Unknown Publisher',
            'total_clicks' => 0,
            'campaigns' => []
        ];
    }
    
    $publisher_performance_summary[$publisher_id]['total_clicks'] += $clicks;
    $publisher_performance_summary[$publisher_id]['campaigns'][] = [
        'name' => $campaigns_map[$campaign_id] ?? 'Unknown Campaign',
        'clicks' => $clicks
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #f8f9fa;
            --success-color: #4ade80;
            --warning-color: #facc15;
            --danger-color: #f87171;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 56px; /* Account for fixed navbar */
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            border-radius: 5px;
            margin: 2px 10px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        
        .table thead th {
            background: rgba(67, 97, 238, 0.1);
            color: var(--dark-text);
            font-weight: 700;
            padding: 15px;
            border: none;
        }
        
        .table tbody tr {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: rgba(67, 97, 238, 0.05);
            transform: scale(1.01);
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border: none;
        }
        
        .quick-select-btn {
            margin: 5px 0;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        .date-display {
            background: linear-gradient(90deg, var(--accent-color) 0%, #4895ef 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
        }
        
        .main-content {
            padding: 30px 0;
            margin-left: 0; /* For mobile */
            margin-top: 56px; /* Account for fixed navbar */
        }
        
        @media (min-width: 992px) {
            .main-content {
                margin-left: 16.666667%; /* Account for sidebar on desktop */
            }
        }
        
        .page-title {
            color: var(--dark-text);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 2px;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        
        .stats-card .label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
                padding-top: 0;
            }
            
            .navbar, .sidebar, .offcanvas, .btn, .date-display {
                display: none !important;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 20px !important;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                border-radius: 0;
                background: white;
            }
            
            .card-header {
                background: #4361ee !important;
                color: white !important;
                border-radius: 0 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .table {
                border-spacing: 0;
            }
            
            .table thead th {
                background: #e9ecef !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .badge {
                border: 1px solid #000;
                color: #000 !important;
                background: transparent !important;
            }
            
            .stats-card {
                border: 1px solid #ddd;
                background: white !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .stats-card i {
                color: #000 !important;
            }
            
            .page-title:after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                <span class="fw-bold text-dark">Ads Platform</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <span class="navbar-text">
                            <i class="fas fa-user-circle me-1"></i>
                            Welcome, <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 d-none d-lg-block bg-light sidebar p-0">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="campaigns.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                <span>Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="advertisers.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Advertisers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="publishers.php">
                                <i class="fas fa-share-alt me-2"></i>
                                <span>Publishers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admins.php">
                                <i class="fas fa-user-shield me-2"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="advertiser_campaigns.php">
                                <i class="fas fa-ad me-2"></i>
                                <span>Advertiser Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="publisher_campaigns.php">
                                <i class="fas fa-link me-2"></i>
                                <span>Publisher Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="daily_report.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>Daily Report</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Mobile Sidebar Toggle -->
            <div class="col-12 d-lg-none bg-light p-2">
                <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="fas fa-bars me-2"></i>Menu
                </button>
            </div>
            
            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
                <div class="offcanvas-header bg-light">
                    <h5 class="offcanvas-title">Navigation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="campaigns.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                <span>Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="advertisers.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Advertisers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="publishers.php">
                                <i class="fas fa-share-alt me-2"></i>
                                <span>Publishers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admins.php">
                                <i class="fas fa-user-shield me-2"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="advertiser_campaigns.php">
                                <i class="fas fa-ad me-2"></i>
                                <span>Advertiser Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="publisher_campaigns.php">
                                <i class="fas fa-link me-2"></i>
                                <span>Publisher Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="daily_report.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>Daily Report</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <main class="col-lg-10 ms-sm-auto px-md-4 py-3 main-content">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0 page-title">Daily Report</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Daily Report</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <!-- Date Display -->
                <div class="text-center mb-4">
                    <div class="date-display">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php 
                        if ($report_start_date === $report_end_date) {
                            echo 'Report Date: ' . date('F j, Y', strtotime($report_date));
                        } else {
                            echo 'Report Period: ' . date('F j, Y', strtotime($report_start_date)) . ' to ' . date('F j, Y', strtotime($report_end_date));
                        }
                        ?>
                    </div>
                </div>

                <!-- Date Selection Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Select Report Date</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="report_date" class="form-label">Custom Date</label>
                                <input type="date" class="form-control" id="report_date" name="date" value="<?php echo htmlspecialchars($report_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Quick Select</label>
                                <div>
                                    <a href="?range=today" class="btn btn-outline-primary btn-sm me-2">Today</a>
                                    <a href="?range=yesterday" class="btn btn-outline-primary btn-sm me-2">Yesterday</a>
                                    <a href="?range=this_month" class="btn btn-outline-primary btn-sm me-2">This Month</a>
                                    <a href="?range=this_year" class="btn btn-outline-primary btn-sm me-2">This Year</a>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Daily Click Summary -->
                <div class="card mb-4" id="dailyClickSummary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Daily Click Summary 
                                <?php 
                                if ($report_start_date === $report_end_date) {
                                    echo '<span class="badge bg-light text-dark ms-2">' . date('F j, Y', strtotime($report_date)) . '</span>';
                                } else {
                                    echo '<span class="badge bg-light text-dark ms-2">' . date('F j, Y', strtotime($report_start_date)) . ' to ' . date('F j, Y', strtotime($report_end_date)) . '</span>';
                                }
                                ?>
                            </h5>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-light" onclick="printDailyClickSummary()">
                                <i class="fas fa-print me-1"></i>Print All
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-4">
                            <table class="table table-hover" id="clickSummaryTable">
                                <thead class="table-primary">
                                    <tr>
                                        <th><i class="fas fa-bullhorn me-1"></i> Campaign</th>
                                        <th><i class="fas fa-users me-1"></i> Advertisers</th>
                                        <th><i class="fas fa-share-alt me-1"></i> Publisher</th>
                                        <th><i class="fas fa-mouse-pointer me-1"></i> Clicks</th>
                                        <th><i class="fas fa-print me-1"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($campaign_click_summary)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i>No click data available for this date range
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($campaign_click_summary as $campaign_id => $summary): ?>
                                            <?php if (!empty($summary['publishers'])): ?>
                                                <?php foreach ($summary['publishers'] as $publisher_id => $publisher_data): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($summary['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($summary['advertisers'])): ?>
                                                            <?php echo htmlspecialchars(implode(', ', $summary['advertisers'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted"><i class="fas fa-ban me-1"></i>None</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars($publisher_data['name']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary fs-6">
                                                            <?php echo number_format($publisher_data['clicks']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="printCampaignRow(this)" data-campaign="<?php echo htmlspecialchars($summary['name']); ?>" data-advertisers="<?php echo htmlspecialchars(implode(', ', $summary['advertisers'] ?? ['None'])); ?>" data-publisher="<?php echo htmlspecialchars($publisher_data['name']); ?>" data-clicks="<?php echo $publisher_data['clicks']; ?>">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($summary['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($summary['advertisers'])): ?>
                                                            <?php echo htmlspecialchars(implode(', ', $summary['advertisers'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted"><i class="fas fa-ban me-1"></i>None</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>No publishers
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary fs-6">0</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="printCampaignRow(this)" data-campaign="<?php echo htmlspecialchars($summary['name']); ?>" data-advertisers="<?php echo htmlspecialchars(implode(', ', $summary['advertisers'] ?? ['None'])); ?>" data-publisher="No publishers" data-clicks="0">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary Stats -->
                        <?php if (!empty($campaign_click_summary)): ?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card bg-primary text-white">
                                    <i class="fas fa-bullhorn"></i>
                                    <span class="number">
                                        <?php 
                                        $total_campaigns = count($campaign_click_summary);
                                        echo number_format($total_campaigns);
                                        ?>
                                    </span>
                                    <span class="label">Total Campaigns</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-success text-white">
                                    <i class="fas fa-share-alt"></i>
                                    <span class="number">
                                        <?php 
                                        $total_publishers = count($publisher_click_summary);
                                        echo number_format($total_publishers);
                                        ?>
                                    </span>
                                    <span class="label">Total Publishers</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-info text-white">
                                    <i class="fas fa-mouse-pointer"></i>
                                    <span class="number">
                                        <?php 
                                        $total_clicks = array_sum(array_column($campaign_click_summary, 'total_clicks'));
                                        echo number_format($total_clicks);
                                        ?>
                                    </span>
                                    <span class="label">Total Clicks</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-warning text-dark">
                                    <i class="fas fa-percentage"></i>
                                    <span class="number">
                                        <?php 
                                        $avg_clicks = $total_campaigns > 0 ? round($total_clicks / $total_campaigns, 2) : 0;
                                        echo number_format($avg_clicks, 2);
                                        ?>
                                    </span>
                                    <span class="label">Avg. Clicks/Campaign</span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add hover effect to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });

        function printDailyClickSummary() {
            const element = document.getElementById('dailyClickSummary');
            const originalContents = document.body.innerHTML;
            
            // Clone the element to avoid modifying the original
            const clone = element.cloneNode(true);
            
            // Remove the stats cards from the clone
            const statsCards = clone.querySelectorAll('.stats-card');
            statsCards.forEach(card => card.remove());
            
            // Remove the row containing stats cards
            const statsRow = clone.querySelector('.row');
            if (statsRow) {
                statsRow.remove();
            }
            
            document.body.innerHTML = clone.innerHTML;
            window.print();
            
            document.body.innerHTML = originalContents;
        }

        function printCampaignRow(button) {
            const campaign = button.getAttribute('data-campaign');
            const advertisers = button.getAttribute('data-advertisers');
            const publisher = button.getAttribute('data-publisher');
            const clicks = button.getAttribute('data-clicks');
            
            const originalContents = document.body.innerHTML;
            
            const printContent = `
                <div style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #4361ee; border-bottom: 2px solid #4361ee; padding-bottom: 10px;">
                        <i class="fas fa-chart-bar"></i> Campaign Report
                    </h2>
                    <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <p><strong>Report Date:</strong> 
                            <?php 
                            if ($report_start_date === $report_end_date) {
                                echo date('F j, Y', strtotime($report_date));
                            } else {
                                echo date('F j, Y', strtotime($report_start_date)) . ' to ' . date('F j, Y', strtotime($report_end_date));
                            }
                            ?>
                        </p>
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                        <thead>
                            <tr style="background: #4361ee; color: white;">
                                <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Campaign</th>
                                <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Advertisers</th>
                                <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Publisher</th>
                                <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 12px; border: 1px solid #ddd;"><strong>${campaign}</strong></td>
                                <td style="padding: 12px; border: 1px solid #ddd;">${advertisers}</td>
                                <td style="padding: 12px; border: 1px solid #ddd;">${publisher}</td>
                                <td style="padding: 12px; border: 1px solid #ddd;">${clicks}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px;">
                        <p>Generated on: <?php echo date('F j, Y g:i A'); ?></p>
                        <p>Ads Platform - Super Admin Report</p>
                    </div>
                </div>
            `;
            
            document.body.innerHTML = printContent;
            window.print();
            
            document.body.innerHTML = originalContents;
        }

    </script>
</body>
</html>