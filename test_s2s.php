<?php
// test_s2s.php - Test S2S Tracking Flow
require_once 'db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>S2S Tracking Test</h2>";

// Check clicks table
echo "<h3>1. Recent Clicks (with click_id):</h3>";
try {
    $stmt = $conn->query("SELECT c.*, camp.name as campaign_name, p.name as publisher_name 
                          FROM clicks c 
                          LEFT JOIN campaigns camp ON c.campaign_id = camp.id
                          LEFT JOIN publishers p ON c.publisher_id = p.id
                          ORDER BY c.clicked_at DESC LIMIT 10");
    $clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($clicks)) {
        echo "<p style='color:orange'>⚠ No clicks recorded yet. Click a publisher tracking link first!</p>";
        
        // Show sample tracking links
        echo "<h4>Sample Tracking Links:</h4>";
        $stmt = $conn->query("
            SELECT c.shortcode, c.name as campaign_name, p.id as publisher_id, p.name as publisher_name
            FROM campaigns c
            JOIN campaign_publishers cp ON c.id = cp.campaign_id
            JOIN publishers p ON cp.publisher_id = p.id
            WHERE c.status = 'active'
            LIMIT 5
        ");
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $path = dirname($_SERVER['REQUEST_URI']);
        
        foreach ($links as $link) {
            $tracking_url = $base_url . $path . "/redirect.php?code=" . $link['shortcode'] . "&pub=" . $link['publisher_id'];
            echo "<p><strong>{$link['campaign_name']}</strong> - {$link['publisher_name']}: ";
            echo "<a href='$tracking_url' target='_blank'>$tracking_url</a></p>";
        }
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>Click ID</th><th>Campaign</th><th>Publisher</th><th>Time</th><th>Test Postback</th></tr>";
        foreach ($clicks as $click) {
            $postback_url = "s2s_postback.php?click_id=" . urlencode($click['click_id']);
            echo "<tr>";
            echo "<td><code style='font-size:10px;'>" . htmlspecialchars(substr($click['click_id'], 0, 20)) . "...</code></td>";
            echo "<td>" . htmlspecialchars($click['campaign_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($click['publisher_name'] ?? 'N/A') . "</td>";
            echo "<td>" . $click['clicked_at'] . "</td>";
            echo "<td><a href='$postback_url' target='_blank' class='btn'>Test Postback</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check S2S conversions
echo "<h3>2. S2S Conversions:</h3>";
try {
    $stmt = $conn->query("SELECT sc.*, camp.name as campaign_name, p.name as publisher_name 
                          FROM s2s_conversions sc 
                          LEFT JOIN campaigns camp ON sc.campaign_id = camp.id
                          LEFT JOIN publishers p ON sc.publisher_id = p.id
                          ORDER BY sc.converted_at DESC LIMIT 10");
    $conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($conversions)) {
        echo "<p style='color:orange'>⚠ No S2S conversions yet.</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>Click ID</th><th>Campaign</th><th>Publisher</th><th>Status</th><th>Time</th></tr>";
        foreach ($conversions as $conv) {
            echo "<tr>";
            echo "<td><code style='font-size:10px;'>" . htmlspecialchars(substr($conv['click_id'], 0, 20)) . "...</code></td>";
            echo "<td>" . htmlspecialchars($conv['campaign_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($conv['publisher_name'] ?? 'N/A') . "</td>";
            echo "<td><span style='color:" . ($conv['status'] == 'approved' ? 'green' : 'orange') . ";'>" . $conv['status'] . "</span></td>";
            echo "<td>" . $conv['converted_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>How to Test:</h3>";
echo "<ol>";
echo "<li>Click any tracking link above (or from publisher dashboard)</li>";
echo "<li>You'll be redirected to target URL with <code>click_id</code> in URL</li>";
echo "<li>Copy the <code>click_id</code> from URL</li>";
echo "<li>Come back here and click 'Test Postback' button</li>";
echo "<li>Conversion will be recorded!</li>";
echo "</ol>";
?>

<style>
.btn {
    background: #4f46e5;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 12px;
}
.btn:hover {
    background: #4338ca;
}
</style>
