<?php
// test_pixel_url.php - Test Pixel URL functionality
require_once 'db_connection.php';

echo "<h2>Pixel URL Test</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if pixel_code column exists
    echo "<h3>1. Checking pixel_code column...</h3>";
    $check = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'pixel_code'");
    if ($check->rowCount() > 0) {
        echo "<p style='color:green;'>✓ pixel_code column exists</p>";
    } else {
        echo "<p style='color:red;'>✗ pixel_code column does NOT exist</p>";
        echo "<p>Run this SQL to add it:</p>";
        echo "<pre>ALTER TABLE campaigns ADD COLUMN pixel_code VARCHAR(50) UNIQUE NULL AFTER shortcode;</pre>";
    }
    
    // Check if conversion_count column exists
    echo "<h3>2. Checking conversion_count column...</h3>";
    $check = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'conversion_count'");
    if ($check->rowCount() > 0) {
        echo "<p style='color:green;'>✓ conversion_count column exists</p>";
    } else {
        echo "<p style='color:red;'>✗ conversion_count column does NOT exist</p>";
        echo "<p>Run this SQL to add it:</p>";
        echo "<pre>ALTER TABLE campaigns ADD COLUMN conversion_count INT DEFAULT 0 AFTER click_count;</pre>";
    }
    
    // Check if conversions table exists
    echo "<h3>3. Checking conversions table...</h3>";
    $check = $conn->query("SHOW TABLES LIKE 'conversions'");
    if ($check->rowCount() > 0) {
        echo "<p style='color:green;'>✓ conversions table exists</p>";
    } else {
        echo "<p style='color:red;'>✗ conversions table does NOT exist</p>";
        echo "<p>Run this SQL to create it:</p>";
        echo "<pre>CREATE TABLE conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    pixel_code VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    converted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pixel_code (pixel_code)
);</pre>";
    }
    
    // Check if daily_conversions table exists
    echo "<h3>4. Checking daily_conversions table...</h3>";
    $check = $conn->query("SHOW TABLES LIKE 'daily_conversions'");
    if ($check->rowCount() > 0) {
        echo "<p style='color:green;'>✓ daily_conversions table exists</p>";
    } else {
        echo "<p style='color:red;'>✗ daily_conversions table does NOT exist</p>";
        echo "<p>Run this SQL to create it:</p>";
        echo "<pre>CREATE TABLE daily_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    conversion_date DATE NOT NULL,
    conversions INT DEFAULT 0,
    UNIQUE KEY unique_campaign_date (campaign_id, conversion_date)
);</pre>";
    }
    
    // List campaigns with pixel codes
    echo "<h3>5. Campaigns with Pixel Codes:</h3>";
    try {
        $stmt = $conn->query("SELECT id, name, shortcode, pixel_code FROM campaigns WHERE pixel_code IS NOT NULL AND pixel_code != '' LIMIT 10");
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($campaigns)) {
            echo "<p>No campaigns with pixel codes found.</p>";
        } else {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Name</th><th>Shortcode</th><th>Pixel Code</th><th>Pixel URL</th></tr>";
            
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            
            foreach ($campaigns as $c) {
                $pixel_url = $base_url . "/pixel.php?p=" . $c['pixel_code'];
                echo "<tr>";
                echo "<td>{$c['id']}</td>";
                echo "<td>{$c['name']}</td>";
                echo "<td>{$c['shortcode']}</td>";
                echo "<td>{$c['pixel_code']}</td>";
                echo "<td><a href='{$pixel_url}' target='_blank'>{$pixel_url}</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // Test pixel.php directly
    echo "<h3>6. Test Pixel URL:</h3>";
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    $test_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $base_path . "/pixel.php?p=TEST123";
    echo "<p>Test URL: <a href='{$test_url}' target='_blank'>{$test_url}</a></p>";
    echo "<p>If the link opens and shows nothing (blank/transparent), the pixel is working!</p>";
    
    // Show correct pixel URL format
    echo "<h3>7. Correct Pixel URL Format:</h3>";
    echo "<p><strong>Your pixel URLs should be:</strong></p>";
    $correct_base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $base_path;
    echo "<pre>{$correct_base}/pixel.php?p=YOUR_PIXEL_CODE</pre>";
    
    // Direct test link
    echo "<h3>8. Direct Test:</h3>";
    echo "<p>Click this to test pixel.php directly: <a href='pixel.php?p=TEST123' target='_blank'>pixel.php?p=TEST123</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
