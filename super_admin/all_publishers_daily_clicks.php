<?php
// super_admin/all_publishers_daily_clicks.php - All Publishers Daily Click Statistics
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Publishers Stats';
$db = Database::getInstance();
$conn = $db->getConnection();

// Date Filter
$filter_type = $_GET['filter'] ?? 'custom';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'today':
            $start_date = $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'previous_month':
            $start_date = date('Y-m-01', strtotime('first day of last month'));
            $end_date = date('Y-m-t', strtotime('last day of last month'));
            break;
    }
}

// Get stats
$stmt = $conn->prepare("
    SELECT p.name as publisher_name, p.id as publisher_id, c.name as campaign_name, c.id as campaign_id,
           SUM(pdc.clicks) as total_clicks, COUNT(DISTINCT pdc.click_date) as active_days
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    JOIN campaigns c ON pdc.campaign_id = c.id
    WHERE pdc.click_date BETWEEN ? AND ?
    GROUP BY p.id, c.id ORDER BY p.name, total_clicks DESC
");
$stmt->execute([$start_date, $end_date]);
$publisher_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_clicks = array_sum(array_column($publisher_summary, 'total_clicks'));
$total_publishers = count(array_unique(array_column($publisher_summary, 'publisher_id')));

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-chart-bar me-2"></i>All Publishers Daily Clicks</h2>
                <p>Aggregated click statistics for all publishers</p>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-lg-5">
                            <label class="form-label fw-semibold small text-muted">Quick Filters</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="?filter=today" class="btn btn-sm btn-outline-primary <?php echo $filter_type == 'today' ? 'active' : ''; ?>">Today</a>
                                <a href="?filter=yesterday" class="btn btn-sm btn-outline-primary <?php echo $filter_type == 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
                                <a href="?filter=this_month" class="btn btn-sm btn-outline-primary <?php echo $filter_type == 'this_month' ? 'active' : ''; ?>">This Month</a>
                                <a href="?filter=previous_month" class="btn btn-sm btn-outline-primary <?php echo $filter_type == 'previous_month' ? 'active' : ''; ?>">Prev Month</a>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <form method="GET" class="row g-2">
                                <input type="hidden" name="filter" value="custom">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Start Date</label>
                                    <input type="date" class="form-control form-control-sm" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">End Date</label>
                                    <input type="date" class="form-control form-control-sm" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-none d-md-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary-custom btn-sm w-100">Apply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
                                <div class="stat-label">Total Clicks</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-mouse-pointer"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo $total_publishers; ?></div>
                                <div class="stat-label">Active Publishers</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo $total_publishers > 0 ? number_format($total_clicks / $total_publishers, 1) : '0'; ?></div>
                                <div class="stat-label">Avg/Publisher</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Publisher Performance</span>
                    <span class="badge bg-primary"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($publisher_summary)): ?>
                        <div class="p-4 text-center text-muted">No data for selected period</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Publisher</th>
                                        <th class="text-end">Clicks</th>
                                        <th class="text-center">Active Days</th>
                                        <th class="text-end">Avg/Day</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publisher_summary as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['publisher_name']); ?></td>
                                        <td class="text-end fw-bold text-primary"><?php echo number_format($row['total_clicks']); ?></td>
                                        <td class="text-center"><?php echo $row['active_days']; ?></td>
                                        <td class="text-end"><?php echo number_format($row['total_clicks'] / max($row['active_days'], 1), 1); ?></td>
                                        <td class="text-center">
                                            <a href="publisher_daily_clicks.php?id=<?php echo $row['campaign_id']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="2" class="fw-bold">Total</td>
                                        <td class="text-end fw-bold text-primary"><?php echo number_format($total_clicks); ?></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
