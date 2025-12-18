<?php
// test_pixel.php - Test Conversion Tracking
require_once 'db_connection.php';

echo "<h2>Conversion Tracking Test</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if pixel_code column exists in campaigns
    echo "<h3>1. Checking Database Structure:</h3>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'pixel_code'");
    $pixel_col = $stmt->fetch();
    if ($pixel_col) {
        echo "<p style='color:green'>✓ pixel_code column exists in campaigns table</p>";
    } else {
        echo "<p style='color:red'>✗ pixel_code column NOT found - Run: ALTER TABLE campaigns ADD COLUMN pixel_code VARCHAR(50) UNIQUE NULL AFTER shortcode;</p>";
    }
    
    $stmt = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'conversion_count'");
    $conv_col = $stmt->fetch();
    if ($conv_col) {
        echo "<p style='color:green'>✓ conversion_count column exists in campaigns table</p>";
    } else {
        echo "<p style='color:red'>✗ conversion_count column NOT found - Run: ALTER TABLE campaigns ADD COLUMN conversion_count INT DEFAULT 0 AFTER click_count;</p>";
    }
    
    // Check conversions table
    $stmt = $conn->query("SHOW TABLES LIKE 'conversions'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>✓ conversions table exists</p>";
    } else {
        echo "<p style='color:red'>✗ conversions table NOT found - Need to create it</p>";
    }
    
    // Check daily_conversions table
    $stmt = $conn->query("SHOW TABLES LIKE 'daily_conversions'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>✓ daily_conversions table exists</p>";
    } else {
        echo "<p style='color:red'>✗ daily_conversions table NOT found - Need to create it</p>";
    }
    
    // List campaigns with pixel codes
    echo "<h3>2. Campaigns with Pixel Codes:</h3>";
    $stmt = $conn->query("SELECT id, name, shortcode, pixel_code, conversion_count FROM campaigns ORDER BY id DESC LIMIT 10");
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($campaigns)) {
        echo "<p>No campaigns found</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Shortcode</th><th>Pixel Code</th><th>Conversions</th><th>Test Link</th></tr>";
        foreach ($campaigns as $c) {
            $pixel = $c['pixel_code'] ?? 'NULL';
            $conv = $c['conversion_count'] ?? '0';
            $testLink = $pixel != 'NULL' ? "<a href='pixel.php?p={$pixel}' target='_blank'>Test Pixel</a>" : '-';
            echo "<tr>";
            echo "<td>{$c['id']}</td>";
            echo "<td>{$c['name']}</td>";
            echo "<td>{$c['shortcode']}</td>";
            echo "<td>{$pixel}</td>";
            echo "<td>{$conv}</td>";
            echo "<td>{$testLink}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show recent conversions
    echo "<h3>3. Recent Conversions:</h3>";
    $stmt = $conn->query("SELECT * FROM conversions ORDER BY converted_at DESC LIMIT 10");
    $conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($conversions)) {
        echo "<p>No conversions recorded yet</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Campaign ID</th><th>Pixel Code</th><th>IP</th><th>Time</th></tr>";
        foreach ($conversions as $conv) {
            echo "<tr>";
            echo "<td>{$conv['id']}</td>";
            echo "<td>{$conv['campaign_id']}</td>";
            echo "<td>{$conv['pixel_code']}</td>";
            echo "<td>{$conv['ip_address']}</td>";
            echo "<td>{$conv['converted_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

<h3>4. Manual Test:</h3>
<p>Enter a pixel code to test:</p>
<form method="GET" action="pixel.php" target="_blank">
    <input type="text" name="p" placeholder="Enter pixel code" style="padding:10px; width:300px;">
    <button type="submit" style="padding:10px;">Test Pixel</button>
</form>

<h3>5. SQL Commands to Fix (if needed):</h3>
<pre style="background:#f5f5f5; padding:15px; border:1px solid #ddd;">
-- Add pixel_code column
ALTER TABLE campaigns ADD COLUMN pixel_code VARCHAR(50) UNIQUE NULL AFTER shortcode;

-- Add conversion_count column  
ALTER TABLE campaigns ADD COLUMN conversion_count INT DEFAULT 0 AFTER click_count;

-- Create conversions table
CREATE TABLE IF NOT EXISTS conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    pixel_code VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    converted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pixel_code (pixel_code),
    INDEX idx_converted_at (converted_at)
);

-- Create daily_conversions table
CREATE TABLE IF NOT EXISTS daily_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    conversion_date DATE NOT NULL,
    conversions INT DEFAULT 0,
    UNIQUE KEY unique_campaign_date (campaign_id, conversion_date),
    INDEX idx_conversion_date (conversion_date)
);
</pre>
