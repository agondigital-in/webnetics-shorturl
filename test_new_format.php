<?php
// test_new_format.php - Test new URL format
require_once 'db_connection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test New URL Format</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        h1 { color: #333; }
        .example { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .example h3 { margin-top: 0; color: #007bff; }
        .url { font-family: monospace; background: #f4f4f4; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 16px; }
        .success { color: green; font-weight: bold; }
        .info { color: #666; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .old { background: #fff3cd; }
        .new { background: #d4edda; }
    </style>
</head>
<body>
<div class="container">
<h1>🎯 New URL Format Test</h1>

<div class="example">
    <h3>✅ Current Format (After Changes)</h3>
    <p class="info">Short codes are now <strong>4 digits</strong> and URLs use <strong>/p</strong> prefix:</p>
    
    <table>
        <tr>
            <th>Campaign Code</th>
            <th>Publisher ID</th>
            <th>Tracking Link</th>
        </tr>
        <tr class="new">
            <td><code>0294</code></td>
            <td>5</td>
            <td><code>yoursite.com/c/0294/p5</code></td>
        </tr>
        <tr class="new">
            <td><code>1234</code></td>
            <td>12</td>
            <td><code>yoursite.com/c/1234/p12</code></td>
        </tr>
        <tr class="new">
            <td><code>5678</code></td>
            <td>23</td>
            <td><code>yoursite.com/c/5678/p23</code></td>
        </tr>
    </table>
</div>

<div class="example">
    <h3>📋 Format Breakdown</h3>
    <div class="url">http://localhost/webnetics-shorturl/c/<strong style="color: blue;">1234</strong>/<strong style="color: green;">p24</strong></div>
    <ul>
        <li><code>c/</code> = Campaign prefix</li>
        <li><code style="color: blue;">1234</code> = Campaign short code (4 random digits)</li>
        <li><code style="color: green;">/p24</code> = Publisher ID 24 (with "p" prefix)</li>
    </ul>
</div>

<div class="example">
    <h3>🔄 Backward Compatibility</h3>
    <p class="info">Old format links will still work:</p>
    
    <table>
        <tr>
            <th>Old Format</th>
            <th>Status</th>
        </tr>
        <tr class="old">
            <td><code>yoursite.com/c/CAMPXIHUCORT/24</code></td>
            <td class="success">✅ Still Works</td>
        </tr>
        <tr class="old">
            <td><code>yoursite.com/c/CAMPSFN329EH/23</code></td>
            <td class="success">✅ Still Works</td>
        </tr>
    </table>
</div>

<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div class='example'>";
    echo "<h3>📊 Your Current Campaigns</h3>";
    
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.name,
            c.shortcode,
            COUNT(DISTINCT psc.publisher_id) as publisher_count
        FROM campaigns c
        LEFT JOIN publisher_short_codes psc ON c.id = psc.campaign_id
        GROUP BY c.id
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($campaigns)) {
        echo "<p class='info'>No campaigns found. Create a new campaign to see the new format!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Campaign Name</th><th>Short Code</th><th>Publishers</th><th>Example Link</th></tr>";
        foreach ($campaigns as $camp) {
            $example_link = "http://localhost/webnetics-shorturl/c/{$camp['shortcode']}/p5";
            echo "<tr>";
            echo "<td>{$camp['name']}</td>";
            echo "<td><code>{$camp['shortcode']}</code></td>";
            echo "<td>{$camp['publisher_count']}</td>";
            echo "<td><code>{$example_link}</code></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='info'><strong>Note:</strong> New campaigns will have 4-digit codes like <code>0294</code>, <code>1234</code>, etc.</p>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h2>✅ What's Changed?</h2>
<ol>
    <li><strong>Short Code Length:</strong> From 12 characters (<code>CAMPMJG5PLCF</code>) to 4 digits (<code>1234</code>)</li>
    <li><strong>URL Format:</strong> Added "p" prefix before publisher ID (<code>/p24</code> instead of <code>/24</code>)</li>
    <li><strong>Cleaner URLs:</strong> Easier to read and share</li>
    <li><strong>Backward Compatible:</strong> Old links still work</li>
</ol>

<p style="margin-top: 30px;">
    <a href="super_admin/add_campaign.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Create New Campaign</a>
    <a href="publisher_dashboard.php" style="padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">View Publisher Dashboard</a>
</p>

</div>
</body>
</html>
