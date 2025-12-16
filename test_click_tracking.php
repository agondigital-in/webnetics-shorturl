<?php
// test_click_tracking.php - Test click tracking
require_once 'db_connection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Click Tracking</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .success { color: green; }
        .error { color: red; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 Click Tracking Test</h1>

<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all campaigns with their publisher assignments
    echo "<h2>Campaigns and Publisher Short Codes</h2>";
    
    $stmt = $conn->prepare("
        SELECT 
            c.id as campaign_id,
            c.name as campaign_name,
            c.shortcode as campaign_shortcode,
            c.status,
            p.id as publisher_id,
            p.name as publisher_name,
            psc.short_code as publisher_shortcode,
            psc.clicks
        FROM campaigns c
        LEFT JOIN publisher_short_codes psc ON c.id = psc.campaign_id
        LEFT JOIN publishers p ON psc.publisher_id = p.id
        ORDER BY c.id, p.id
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "<p class='error'>No campaigns found!</p>";
    } else {
        echo "<table>";
        echo "<tr>
                <th>Campaign ID</th>
                <th>Campaign Name</th>
                <th>Campaign Code</th>
                <th>Status</th>
                <th>Publisher ID</th>
                <th>Publisher Name</th>
                <th>Publisher Code</th>
                <th>Clicks</th>
                <th>Test Link</th>
              </tr>";
        
        foreach ($results as $row) {
            $test_link = "http://localhost/webnetics-shorturl/c/" . $row['publisher_shortcode'] . "/" . $row['publisher_id'];
            
            echo "<tr>";
            echo "<td>{$row['campaign_id']}</td>";
            echo "<td>{$row['campaign_name']}</td>";
            echo "<td><code>{$row['campaign_shortcode']}</code></td>";
            echo "<td><span class='" . ($row['status'] == 'active' ? 'success' : 'error') . "'>{$row['status']}</span></td>";
            echo "<td>{$row['publisher_id']}</td>";
            echo "<td>{$row['publisher_name']}</td>";
            echo "<td><code>{$row['publisher_shortcode']}</code></td>";
            echo "<td><strong>{$row['clicks']}</strong></td>";
            echo "<td><a href='{$test_link}' target='_blank'>Test Click</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Check for mismatches
    echo "<h2>⚠️ Potential Issues</h2>";
    
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.name,
            c.shortcode as campaign_code,
            GROUP_CONCAT(DISTINCT psc.short_code) as publisher_codes
        FROM campaigns c
        LEFT JOIN publisher_short_codes psc ON c.id = psc.campaign_id
        GROUP BY c.id
        HAVING COUNT(DISTINCT psc.short_code) > 1
    ");
    $stmt->execute();
    $mismatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($mismatches)) {
        echo "<p class='success'>✓ All campaigns have consistent short codes!</p>";
    } else {
        echo "<p class='error'>Found campaigns with multiple short codes:</p>";
        echo "<table>";
        echo "<tr><th>Campaign ID</th><th>Campaign Name</th><th>Campaign Code</th><th>Publisher Codes</th></tr>";
        foreach ($mismatches as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td><code>{$row['campaign_code']}</code></td>";
            echo "<td><code>{$row['publisher_codes']}</code></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><a href='fix_duplicate_shortcodes.php'>→ Run Fix Script</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>

</div>
</body>
</html>
