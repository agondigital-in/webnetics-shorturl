<?php
// debug_pixel.php - Debug Conversion Tracking
header('Content-Type: text/html');
require_once 'db_connection.php';

$pixel_code = $_GET['p'] ?? '';

echo "<h2>Pixel Debug for: " . htmlspecialchars($pixel_code) . "</h2>";

if (empty($pixel_code)) {
    echo "<p style='color:red'>❌ No pixel code provided. Use ?p=YOUR_PIXEL_CODE</p>";
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Step 1: Check pixel_code column
    echo "<h3>Step 1: Check pixel_code column</h3>";
    $check = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'pixel_code'");
    if ($check->rowCount() == 0) {
        echo "<p style='color:red'>❌ pixel_code column does NOT exist!</p>";
        exit;
    }
    echo "<p style='color:green'>✓ pixel_code column exists</p>";
    
    // Step 2: Find campaign
    echo "<h3>Step 2: Find campaign by pixel code</h3>";
    $stmt = $conn->prepare("SELECT id, name, status, pixel_code, conversion_count FROM campaigns WHERE pixel_code = ?");
    $stmt->execute([$pixel_code]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        echo "<p style='color:red'>❌ No campaign found with pixel_code: " . htmlspecialchars($pixel_code) . "</p>";
        
        // Show all campaigns with pixel codes
        echo "<h4>Available campaigns with pixel codes:</h4>";
        $stmt = $conn->query("SELECT id, name, pixel_code, status FROM campaigns WHERE pixel_code IS NOT NULL");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($all, true) . "</pre>";
        exit;
    }
    
    echo "<p style='color:green'>✓ Campaign found:</p>";
    echo "<pre>" . print_r($campaign, true) . "</pre>";
    
    if ($campaign['status'] != 'active') {
        echo "<p style='color:red'>❌ Campaign status is NOT active: " . $campaign['status'] . "</p>";
        exit;
    }
    echo "<p style='color:green'>✓ Campaign is active</p>";
    
    $campaign_id = $campaign['id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $today = date('Y-m-d');
    
    // Step 3: Check conversions table
    echo "<h3>Step 3: Check conversions table</h3>";
    $tableCheck = $conn->query("SHOW TABLES LIKE 'conversions'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p style='color:red'>❌ conversions table does NOT exist!</p>";
        exit;
    }
    echo "<p style='color:green'>✓ conversions table exists</p>";
    
    // Step 4: Check for duplicate
    echo "<h3>Step 4: Check duplicate (same IP within 1 minute)</h3>";
    echo "<p>Your IP: " . htmlspecialchars($ip_address) . "</p>";
    
    $stmt = $conn->prepare("
        SELECT id, converted_at FROM conversions 
        WHERE campaign_id = ? AND ip_address = ? AND converted_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$campaign_id, $ip_address]);
    $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($duplicate) {
        echo "<p style='color:orange'>⚠ Duplicate found (wait 1 minute):</p>";
        echo "<pre>" . print_r($duplicate, true) . "</pre>";
    } else {
        echo "<p style='color:green'>✓ No duplicate - conversion will be recorded</p>";
        
        // Step 5: Try to insert
        echo "<h3>Step 5: Insert conversion</h3>";
        try {
            $stmt = $conn->prepare("
                INSERT INTO conversions (campaign_id, pixel_code, ip_address, user_agent, referrer) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$campaign_id, $pixel_code, $ip_address, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '']);
            
            if ($result) {
                echo "<p style='color:green'>✓ Conversion inserted! ID: " . $conn->lastInsertId() . "</p>";
            } else {
                echo "<p style='color:red'>❌ Insert failed</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Insert error: " . $e->getMessage() . "</p>";
        }
        
        // Step 6: Update campaign count
        echo "<h3>Step 6: Update campaign conversion_count</h3>";
        try {
            $stmt = $conn->prepare("UPDATE campaigns SET conversion_count = conversion_count + 1 WHERE id = ?");
            $result = $stmt->execute([$campaign_id]);
            $affected = $stmt->rowCount();
            
            echo "<p>Rows affected: " . $affected . "</p>";
            
            // Check new count
            $stmt = $conn->prepare("SELECT conversion_count FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            $newCount = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color:green'>✓ New conversion_count: " . $newCount['conversion_count'] . "</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Update error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Show recent conversions for this campaign
    echo "<h3>Recent conversions for this campaign:</h3>";
    $stmt = $conn->prepare("SELECT * FROM conversions WHERE campaign_id = ? ORDER BY converted_at DESC LIMIT 10");
    $stmt->execute([$campaign_id]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($recent, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
