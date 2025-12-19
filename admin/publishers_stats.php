<?php
// admin/publishers_stats.php - All Publishers Daily Click Statistics (Modern UI with Permissions)
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';
require_once 'includes/check_permission.php';

$page_title = 'Publishers Stats';
$db = Database::getInstance();
$conn = $db->getConnection();

$admin_permissions = getAdminPermissions($conn, $_SESSION['user_id']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Check permission
if (!$is_super_admin && !in_array('publishers_stats_view', $admin_permissions)) {
    header('Location: dashboard.php');
    exit();
}

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

// Get all campaigns with clicks and conversions
$stmt = $conn->prepare("
    SELECT c.id as campaign_id, c.name as campaign_name, c.shortcode, c.click_count,
           COALESCE((SELECT SUM(clicks) FROM publisher_daily_clicks pdc WHERE pdc.campaign_id = c.id AND pdc.click_date BETWEEN ? AND ?), 0) as period_clicks
    FROM campaigns c
    ORDER BY c.name
");
$stmt->execute([$start_date, $end_date]);
$campaign_conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if pixel_code and conversion_count columns exist
$has_pixel_columns = false;
try {
    $check = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'pixel_code'");
    $has_pixel_columns = $check->rowCount() > 0;
} catch (Exception $e) {
    $has_pixel_columns = false;
}

// Check if publisher_pixel_codes table exists
$has_publisher_pixels = false;
try {
    $check = $conn->query("SHOW TABLES LIKE 'publisher_pixel_codes'");
    $has_publisher_pixels = $check->rowCount() > 0;
} catch (Exception $e) {
    $has_publisher_pixels = false;
}

if ($has_publisher_pixels) {
    $stmt = $conn->prepare("
        SELECT 
            c.id as campaign_id, c.name as campaign_name, ppc.pixel_code,
            COALESCE(ppc.conversion_count, 0) as publisher_conversions,
            p.id as publisher_id, p.name as publisher_name,
            COALESCE(SUM(pdc.clicks), 0) as total_clicks,
            COALESCE(ppc.conversion_count, 0) as total_conversions
        FROM campaigns c
        JOIN campaign_publishers cp ON c.id = cp.campaign_id
        JOIN publishers p ON cp.publisher_id = p.id
        LEFT JOIN publisher_pixel_codes ppc ON c.id = ppc.campaign_id AND p.id = ppc.publisher_id
        LEFT JOIN publisher_daily_clicks pdc ON c.id = pdc.campaign_id AND p.id = pdc.publisher_id AND pdc.click_date BETWEEN ? AND ?
        GROUP BY c.id, c.name, ppc.pixel_code, ppc.conversion_count, p.id, p.name
        ORDER BY c.name, p.name
    ");
} elseif ($has_pixel_columns) {
    $stmt = $conn->prepare("
        SELECT 
            c.id as campaign_id, c.name as campaign_name, c.pixel_code,
            p.id as publisher_id, p.name as publisher_name,
            COALESCE(SUM(pdc.clicks), 0) as total_clicks,
            COALESCE(c.conversion_count, 0) as total_conversions
        FROM campaigns c
        JOIN campaign_publishers cp ON c.id = cp.campaign_id
        JOIN publishers p ON cp.publisher_id = p.id
        LEFT JOIN publisher_daily_clicks pdc ON c.id = pdc.campaign_id AND p.id = pdc.publisher_id AND pdc.click_date BETWEEN ? AND ?
        GROUP BY c.id, c.name, c.pixel_code, p.id, p.name, c.conversion_count
        ORDER BY c.name, p.name
    ");
} else {
    $stmt = $conn->prepare("
        SELECT 
            c.id as campaign_id, c.name as campaign_name, NULL as pixel_code,
            p.id as publisher_id, p.name as publisher_name,
            COALESCE(SUM(pdc.clicks), 0) as total_clicks,
            0 as total_conversions
        FROM campaigns c
        JOIN campaign_publishers cp ON c.id = cp.campaign_id
        JOIN publishers p ON cp.publisher_id = p.id
        LEFT JOIN publisher_daily_clicks pdc ON c.id = pdc.campaign_id AND p.id = pdc.publisher_id AND pdc.click_date BETWEEN ? AND ?
        GROUP BY c.id, c.name, p.id, p.name
        ORDER BY c.name, p.name
    ");
}
$stmt->execute([$start_date, $end_date]);
$publisher_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_clicks = array_sum(array_column($publisher_summary, 'total_clicks'));
$total_conversions = array_sum(array_column($publisher_summary, 'total_conversions'));

// Get total campaigns count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM campaigns");
$stmt->execute();
$total_campaigns = $stmt->fetch()['count'];

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<style>
.stat-card-gradient {
    border-radius: 12px;
    padding: 20px;
    color: white;
}
.stat-card-gradient .stat-value { font-size: 28px; font-weight: 700; }
.stat-card-gradient .stat-label { font-size: 14px; opacity: 0.9; }
.stat-card-gradient .stat-icon { font-size: 24px; opacity: 0.8; }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header mb-4">
                <h2 class="mb-1"><i class="fas fa-chart-bar me-2"></i>All Publishers Daily Clicks</h2>
                <p class="text-muted mb-0">Aggregated click statistics for all publishers</p>
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
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
                                <div class="stat-label">Total Clicks</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-mouse-pointer"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_conversions); ?></div>
                                <div class="stat-label">Conversions</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo $total_campaigns; ?></div>
                                <div class="stat-label">Campaigns</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo $total_clicks > 0 ? number_format(($total_conversions / $total_clicks) * 100, 1) : '0'; ?>%</div>
                                <div class="stat-label">Conv. Rate</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campaign Clicks Table -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <span><i class="fas fa-bullhorn me-2"></i>Campaign Clicks Summary</span>
                    <span class="badge bg-light text-primary"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($campaign_conversions)): ?>
                        <div class="p-4 text-center text-muted">No campaigns found</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th class="text-center">Short Code</th>
                                        <th class="text-end">Period Clicks</th>
                                        <th class="text-end">Total Clicks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaign_conversions as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                        <td class="text-center">
                                            <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($row['shortcode']); ?></code>
                                        </td>
                                        <td class="text-end fw-bold text-success"><?php echo number_format($row['period_clicks']); ?></td>
                                        <td class="text-end fw-bold text-primary"><?php echo number_format($row['click_count']); ?></td>
                                        <td class="text-center">
                                            <a href="campaign_tracking_stats.php?id=<?php echo $row['campaign_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="2" class="fw-bold">Total</td>
                                        <td class="text-end fw-bold text-success"><?php echo number_format(array_sum(array_column($campaign_conversions, 'period_clicks'))); ?></td>
                                        <td class="text-end fw-bold text-primary"><?php echo number_format(array_sum(array_column($campaign_conversions, 'click_count'))); ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Publisher Performance Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Publisher Performance</span>
                    <span class="badge bg-primary"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($publisher_summary)): ?>
                        <div class="p-4 text-center text-muted">No publishers assigned to campaigns</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Publisher</th>
                                        <th class="text-end">Clicks</th>
                                        <th class="text-end">Conversions</th>
                                        <th class="text-center">Conv. Rate</th>
                                        <th class="text-center">S2S Pixel</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publisher_summary as $row): ?>
                                    <?php $conv_rate = $row['total_clicks'] > 0 ? ($row['total_conversions'] / $row['total_clicks']) * 100 : 0; ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['publisher_name']); ?></td>
                                        <td class="text-end fw-bold text-primary"><?php echo number_format($row['total_clicks']); ?></td>
                                        <td class="text-end fw-bold text-success"><?php echo number_format($row['total_conversions']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $conv_rate >= 5 ? 'success' : ($conv_rate >= 2 ? 'warning' : 'secondary'); ?>">
                                                <?php echo number_format($conv_rate, 1); ?>%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-info" onclick="showPixelCode(<?php echo $row['campaign_id']; ?>, <?php echo $row['publisher_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['campaign_name'])); ?>', '<?php echo htmlspecialchars(addslashes($row['publisher_name'])); ?>')" title="View S2S Pixel">
                                                <i class="fas fa-server"></i> S2S
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <a href="publisher_daily_clicks.php?id=<?php echo $row['campaign_id']; ?>&publisher_id=<?php echo $row['publisher_id']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
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
                                        <td class="text-end fw-bold text-success"><?php echo number_format($total_conversions); ?></td>
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

<!-- Conversion Pixel Modal -->
<div class="modal fade" id="pixelModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-code me-2"></i>Conversion Pixels (For Advertiser)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Campaign:</strong> <span id="modalCampaignName"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Publisher:</strong> <span id="modalPublisherName"></span></p>
                    </div>
                </div>
                
                <!-- 1. S2S Pixel -->
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary text-white py-2">
                        <i class="fas fa-server me-2"></i>🔹 S2S Pixel (Server-to-Server Pixel)
                    </div>
                    <div class="card-body">
                        <p class="small mb-2">👉 <strong>Server direct server se data bhejta hai</strong></p>
                        <p class="small text-muted mb-2">
                            <strong>Kaise kaam karta hai:</strong> Website/app ka server → Ad/Affiliate server<br>
                            <strong>Use:</strong> Conversion tracking, Affiliate offers, Payment confirmation<br>
                            <span class="text-success">✅ Accurate ✅ Ad-blocker safe</span>
                        </p>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalS2sUrl" readonly>
                            <button class="btn btn-primary" type="button" onclick="copyPixelText('modalS2sUrl')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 2. Image Pixel -->
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success text-white py-2">
                        <i class="fas fa-image me-2"></i>🔹 Image Pixel (Tracking Pixel)
                    </div>
                    <div class="card-body">
                        <p class="small mb-2">👉 <strong>1×1 transparent image hoti hai</strong></p>
                        <p class="small text-muted mb-2">
                            <strong>Use:</strong> Email open tracking, Page visit tracking, Thank you page<br>
                            <strong>Kaise lagaye:</strong> Thank you page ke HTML mein paste karein
                        </p>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalImagePixel" readonly>
                            <button class="btn btn-success" type="button" onclick="copyPixelText('modalImagePixel')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 3. JavaScript Pixel -->
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-warning text-dark py-2">
                        <i class="fas fa-code me-2"></i>🔹 JavaScript Pixel
                    </div>
                    <div class="card-body">
                        <p class="small mb-2">👉 <strong>JS code ke through tracking</strong></p>
                        <p class="small text-muted mb-2">
                            <strong>Use:</strong> Button click, Scroll tracking, Custom events, Form submit<br>
                            <strong>Kaise lagaye:</strong> Thank you page ke &lt;head&gt; ya &lt;body&gt; mein paste karein
                        </p>
                        <div class="input-group">
                            <textarea class="form-control" id="modalJsPixel" rows="4" readonly style="font-family: monospace; font-size: 12px;"></textarea>
                            <button class="btn btn-warning" type="button" onclick="copyPixelText('modalJsPixel')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 4. Postback URL -->
                <div class="card mb-3 border-danger">
                    <div class="card-header bg-danger text-white py-2">
                        <i class="fas fa-link me-2"></i>🔹 Postback URL Pixel
                    </div>
                    <div class="card-body">
                        <p class="small mb-2">👉 <strong>Affiliate marketing mein popular</strong></p>
                        <p class="small text-muted mb-2">
                            <strong>Kaise kaam karta hai:</strong> Conversion ke baad URL hit hota hai<br>
                            <strong>Use:</strong> Affiliate networks, CPA offers, Lead tracking
                        </p>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalPostbackUrl" readonly>
                            <button class="btn btn-danger" type="button" onclick="copyPixelText('modalPostbackUrl')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <p class="small text-muted mt-2 mb-0"><strong>Optional Parameters:</strong> <code>&txn_id=ORDER123</code> <code>&payout=10.00</code> <code>&status=success</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showPixelCode(campaignId, publisherId, campaignName, publisherName) {
    var pathArray = window.location.pathname.split('/');
    pathArray.pop(); // Remove current file
    pathArray.pop(); // Remove admin folder
    var basePath = pathArray.join('/');
    var baseUrl = window.location.origin + basePath;
    
    var pubCode = btoa(campaignId + ':' + publisherId + ':s2s');
    
    // 1. S2S Postback URL
    var s2sUrl = baseUrl + '/s2s_pixel.php?pub=' + pubCode;
    
    // 2. Image Pixel
    var imagePixel = '<img src="' + s2sUrl + '" width="1" height="1" style="display:none;" alt="" />';
    
    // 3. JavaScript Pixel
    var jsPixel = '<script>\n(function() {\n  var img = new Image();\n  img.src = "' + s2sUrl + '";\n})();\n<\/script>';
    
    // 4. Postback URL
    var postbackUrl = s2sUrl;
    
    document.getElementById('modalCampaignName').textContent = campaignName;
    document.getElementById('modalPublisherName').textContent = publisherName || 'N/A';
    document.getElementById('modalS2sUrl').value = s2sUrl;
    document.getElementById('modalImagePixel').value = imagePixel;
    document.getElementById('modalJsPixel').value = jsPixel;
    document.getElementById('modalPostbackUrl').value = postbackUrl;
    
    var modal = new bootstrap.Modal(document.getElementById('pixelModal'));
    modal.show();
}

function copyPixelText(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(copyText.value).then(function() {
        var btn = copyText.nextElementSibling;
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        
        setTimeout(function() {
            btn.innerHTML = originalHtml;
        }, 2000);
    }).catch(function(err) {
        document.execCommand('copy');
        alert('Copied!');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
