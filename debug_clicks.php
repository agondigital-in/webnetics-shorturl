<?php
// debug_clicks.php - Debug click counts
require_once 'db_connection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Click Counts</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .zero { color: red; font-weight: bold; }
        .positive { color: green; font-weight: bold; }
        h2 { color: #333; margin-top: 30px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 Debug Click Counts</h1>

<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check publisher_short_codes table
    echo "<h2>1. Publisher Short Codes Table</h2>";
    $stmt = $conn->prepare("
        SELECT 
            psc.campaign_id,
            c.name as campaign_name,
            psc.publisher_id,
            p.name as publisher_name,
            psc.short_code,
            psc.clicks
        FROM publisher_short_codes psc
        JOIN campaigns c ON psc.campaign_id = c.id
        JOIN publishers p ON psc.publisher_id = p.id
        ORDER BY psc.campaign_id, psc.publisher_id
    ");
    $stmt->execute();
    $psc_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($psc_data)) {
        echo "<p style='color: red;'>❌ No data in publisher_short_codes table!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Campaign ID</th><th>Campaign</th><th>Publisher ID</th><th>Publisher</th><th>Short Code</th><th>Clicks</th></tr>";
        foreach ($psc_data as $row) {
            $click_class = $row['clicks'] > 0 ? 'positive' : 'zero';
            echo "<tr>";
            echo "<td>{$row['campaign_id']}</td>";
            echo "<td>{$row['campaign_name']}</td>";
            echo "<td>{$row['publisher_id']}</td>";
            echo "<td>{$row['publisher_name']}</td>";
            echo "<td><code>{$row['short_code']}</code></td>";
            echo "<td class='{$click_class}'>{$row['clicks']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check campaign_publishers table
    echo "<h2>2. Campaign Publishers Table</h2>";
    $stmt = $conn->prepare("
        SELECT 
            cp.campaign_id,
            c.name as campaign_name,
            cp.publisher_id,
            p.name as publisher_name,
            cp.clicks
        FROM campaign_publishers cp
        JOIN campaigns c ON cp.campaign_id = c.id
        JOIN publishers p ON cp.publisher_id = p.id
        ORDER BY cp.campaign_id, cp.publisher_id
    ");
    $stmt->execute();
    $cp_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cp_data)) {
        echo "<p style='color: red;'>❌ No data in campaign_publishers table!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Campaign ID</th><th>Campaign</th><th>Publisher ID</th><th>Publisher</th><th>Clicks</th></tr>";
        foreach ($cp_data as $row) {
            $click_class = $row['clicks'] > 0 ? 'positive' : 'zero';
            echo "<tr>";
            echo "<td>{$row['campaign_id']}</td>";
            echo "<td>{$row['campaign_name']}</td>";
            echo "<td>{$row['publisher_id']}</td>";
            echo "<td>{$row['publisher_name']}</td>";
            echo "<td class='{$click_class}'>{$row['clicks']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check campaigns table
    echo "<h2>3. Campaigns Table (Total Clicks)</h2>";
    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            shortcode,
            click_count,
            status
        FROM campaigns
        ORDER BY id
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Campaign Name</th><th>Short Code</th><th>Total Clicks</th><th>Status</th></tr>";
    foreach ($campaigns as $row) {
        $click_class = $row['click_count'] > 0 ? 'positive' : 'zero';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td><code>{$row['shortcode']}</code></td>";
        echo "<td class='{$click_class}'>{$row['click_count']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check daily clicks
    echo "<h2>4. Publisher Daily Clicks</h2>";
    $stmt = $conn->prepare("
        SELECT 
            pdc.campaign_id,
            c.name as campaign_name,
            pdc.publisher_id,
            p.name as publisher_name,
            pdc.click_date,
            pdc.clicks
        FROM publisher_daily_clicks pdc
        JOIN campaigns c ON pdc.campaign_id = c.id
        JOIN publishers p ON pdc.publisher_id = p.id
        ORDER BY pdc.click_date DESC, pdc.campaign_id
        LIMIT 50
    ");
    $stmt->execute();
    $daily_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($daily_clicks)) {
        echo "<p style='color: red;'>❌ No daily click data!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Campaign ID</th><th>Campaign</th><th>Publisher ID</th><th>Publisher</th><th>Date</th><th>Clicks</th></tr>";
        foreach ($daily_clicks as $row) {
            echo "<tr>";
            echo "<td>{$row['campaign_id']}</td>";
            echo "<td>{$row['campaign_name']}</td>";
            echo "<td>{$row['publisher_id']}</td>";
            echo "<td>{$row['publisher_name']}</td>";
            echo "<td>{$row['click_date']}</td>";
            echo "<td class='positive'>{$row['clicks']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Summary
    echo "<h2>📊 Summary</h2>";
    echo "<ul>";
    
    $total_psc_clicks = array_sum(array_column($psc_data, 'clicks'));
    $total_cp_clicks = array_sum(array_column($cp_data, 'clicks'));
    $total_campaign_clicks = array_sum(array_column($campaigns, 'click_count'));
    $total_daily_clicks = array_sum(array_column($daily_clicks, 'clicks'));
    
    echo "<li><strong>Total clicks in publisher_short_codes:</strong> {$total_psc_clicks}</li>";
    echo "<li><strong>Total clicks in campaign_publishers:</strong> {$total_cp_clicks}</li>";
    echo "<li><strong>Total clicks in campaigns:</strong> {$total_campaign_clicks}</li>";
    echo "<li><strong>Total daily clicks (last 50 records):</strong> {$total_daily_clicks}</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>

<hr>
<p><a href="test_click_tracking.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">← Back to Test Page</a></p>

</div>
</body>
</html>
