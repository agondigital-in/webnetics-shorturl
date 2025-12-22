<?php
// test_fingerprint_tracking.php - Test IP + Fingerprint based conversion tracking
// This tests conversion tracking WITHOUT passing click_id parameters

require_once 'db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>🔍 Fingerprint-Based Conversion Tracking Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} code{background:#f0f0f0;padding:2px 6px;}</style>";

// Step 1: Check if table exists
echo "<h2>Step 1: Check Database Table</h2>";
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'click_fingerprints'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p class='success'>✅ click_fingerprints table exists</p>";
        
        // Show recent clicks
        $stmt = $conn->query("SELECT * FROM click_fingerprints ORDER BY click_time DESC LIMIT 5");
        $clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($clicks) > 0) {
            echo "<h3>Recent Click Fingerprints:</h3>";
            echo "<table border='1' cellpadding='5'><tr><th>Click ID</th><th>Campaign</th><th>Publisher</th><th>IP</th><th>Fingerprint</th><th>Time</th><th>Converted</th></tr>";
            foreach ($clicks as $click) {
                echo "<tr>";
                echo "<td>" . substr($click['click_id'], 0, 20) . "...</td>";
                echo "<td>{$click['campaign_id']}</td>";
                echo "<td>{$click['publisher_id']}</td>";
                echo "<td>{$click['ip_address']}</td>";
                echo "<td>" . substr($click['fingerprint'], 0, 20) . "...</td>";
                echo "<td>{$click['click_time']}</td>";
                echo "<td>" . ($click['converted'] ? '✅ Yes' : '❌ No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='info'>ℹ️ No clicks recorded yet. Click a tracking link first.</p>";
        }
    } else {
        echo "<p class='error'>❌ click_fingerprints table does not exist. Run the SQL file first.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 2: Show current user fingerprint
echo "<h2>Step 2: Your Current Fingerprint</h2>";
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fingerprint = hash('sha256', $ip . '|' . $user_agent);

echo "<p><strong>Your IP:</strong> <code>$ip</code></p>";
echo "<p><strong>Your User Agent:</strong> <code>" . htmlspecialchars(substr($user_agent, 0, 80)) . "...</code></p>";
echo "<p><strong>Your Fingerprint:</strong> <code>" . substr($fingerprint, 0, 32) . "...</code></p>";

// Step 3: Check if there's a matching click for this user
echo "<h2>Step 3: Check Matching Clicks</h2>";
try {
    $stmt = $conn->prepare("
        SELECT cf.*, c.name as campaign_name, p.name as publisher_name
        FROM click_fingerprints cf
        JOIN campaigns c ON cf.campaign_id = c.id
        JOIN publishers p ON cf.publisher_id = p.id
        WHERE cf.fingerprint = ? AND cf.converted = FALSE
        ORDER BY cf.click_time DESC
        LIMIT 5
    ");
    $stmt->execute([$fingerprint]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($matches) > 0) {
        echo "<p class='success'>✅ Found " . count($matches) . " unconverted click(s) for your fingerprint!</p>";
        echo "<table border='1' cellpadding='5'><tr><th>Campaign</th><th>Publisher</th><th>Click Time</th><th>Test Conversion</th></tr>";
        foreach ($matches as $match) {
            $pub_code = base64_encode($match['campaign_id'] . ':' . $match['publisher_id'] . ':s2s');
            echo "<tr>";
            echo "<td>{$match['campaign_name']}</td>";
            echo "<td>{$match['publisher_name']}</td>";
            echo "<td>{$match['click_time']}</td>";
            echo "<td><a href='s2s_pixel.php?pub=$pub_code' target='_blank'>🔥 Fire Conversion</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='info'>ℹ️ Click 'Fire Conversion' to test - it will match based on your IP+Fingerprint without needing click_id!</p>";
    } else {
        echo "<p class='info'>ℹ️ No unconverted clicks found for your fingerprint.</p>";
        echo "<p>To test: First click a tracking link, then come back here and fire a conversion.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 4: How it works
echo "<h2>Step 4: How Fingerprint Tracking Works</h2>";
echo "<div style='background:#f9f9f9;padding:15px;border-radius:8px;'>";
echo "<h3>🔄 Flow:</h3>";
echo "<ol>";
echo "<li><strong>User clicks tracking link</strong> → redirect.php stores IP + User Agent fingerprint</li>";
echo "<li><strong>User converts on advertiser site</strong> → Advertiser fires S2S pixel</li>";
echo "<li><strong>S2S pixel matches conversion</strong> → Uses 3 methods in order:
    <ul>
        <li>1️⃣ <strong>click_id</strong> (if passed) - Most accurate</li>
        <li>2️⃣ <strong>IP + User Agent fingerprint</strong> - Very accurate</li>
        <li>3️⃣ <strong>IP only</strong> - Fallback, less accurate</li>
    </ul>
</li>";
echo "<li><strong>Conversion attributed</strong> → Publisher gets credit!</li>";
echo "</ol>";

echo "<h3>⏰ Attribution Window:</h3>";
echo "<p>Default: <strong>24 hours</strong> - Conversion must happen within 24 hours of click.</p>";
echo "<p>Can be changed per campaign in the <code>attribution_window</code> column.</p>";

echo "<h3>📊 Match Methods:</h3>";
echo "<ul>";
echo "<li><code>click_id</code> - Advertiser passed click_id parameter ✅ Best</li>";
echo "<li><code>fingerprint</code> - Matched by IP + User Agent ✅ Good</li>";
echo "<li><code>ip_only</code> - Matched by IP only ⚠️ Okay</li>";
echo "<li><code>unmatched</code> - No click found ❌ Still recorded but unattributed</li>";
echo "</ul>";
echo "</div>";

// Step 5: Recent conversions
echo "<h2>Step 5: Recent Conversions</h2>";
try {
    // First check what columns exist in s2s_conversions
    $columns = $conn->query("SHOW COLUMNS FROM s2s_conversions")->fetchAll(PDO::FETCH_COLUMN);
    $time_column = in_array('created_at', $columns) ? 'created_at' : (in_array('conversion_time', $columns) ? 'conversion_time' : 'id');
    
    $stmt = $conn->query("
        SELECT sc.*, c.name as campaign_name, p.name as publisher_name
        FROM s2s_conversions sc
        JOIN campaigns c ON sc.campaign_id = c.id
        JOIN publishers p ON sc.publisher_id = p.id
        ORDER BY sc.$time_column DESC
        LIMIT 10
    ");
    $conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($conversions) > 0) {
        echo "<table border='1' cellpadding='5'><tr><th>Campaign</th><th>Publisher</th><th>Click ID</th><th>IP</th><th>Time</th></tr>";
        foreach ($conversions as $conv) {
            $click_type = '';
            if (strpos($conv['click_id'], 'UNMATCHED_') === 0) {
                $click_type = ' <span style="color:red">(Unmatched)</span>';
            } elseif (strpos($conv['click_id'], 'S2S_') === 0) {
                $click_type = ' <span style="color:orange">(Legacy)</span>';
            } else {
                $click_type = ' <span style="color:green">(Matched)</span>';
            }
            $time_value = $conv['created_at'] ?? $conv['conversion_time'] ?? $conv['id'] ?? 'N/A';
            echo "<tr>";
            echo "<td>{$conv['campaign_name']}</td>";
            echo "<td>{$conv['publisher_name']}</td>";
            echo "<td>" . substr($conv['click_id'], 0, 25) . "...$click_type</td>";
            echo "<td>{$conv['ip_address']}</td>";
            echo "<td>$time_value</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ No conversions recorded yet.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
