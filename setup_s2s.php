<?php
// setup_s2s.php - Setup S2S Conversion Tracking Tables
require_once 'db_connection.php';

echo "<h2>Setup S2S (Server-to-Server) Conversion Tracking</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create clicks table
    echo "<h3>1. Creating clicks table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `clicks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `click_id` VARCHAR(64) UNIQUE NOT NULL,
        `campaign_id` INT NOT NULL,
        `publisher_id` INT NOT NULL,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `referrer` TEXT,
        `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_click_id` (`click_id`),
        INDEX `idx_campaign_publisher` (`campaign_id`, `publisher_id`)
    )";
    $conn->exec($sql);
    echo "<p style='color:green'>✓ clicks table created</p>";
    
    // Create s2s_conversions table
    echo "<h3>2. Creating s2s_conversions table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `s2s_conversions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `click_id` VARCHAR(64) NOT NULL,
        `campaign_id` INT NOT NULL,
        `publisher_id` INT NOT NULL,
        `transaction_id` VARCHAR(100) NULL,
        `payout` DECIMAL(10,2) NULL,
        `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
        `ip_address` VARCHAR(45),
        `converted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_click_conversion` (`click_id`),
        INDEX `idx_campaign_id` (`campaign_id`),
        INDEX `idx_publisher_id` (`publisher_id`),
        INDEX `idx_status` (`status`)
    )";
    $conn->exec($sql);
    echo "<p style='color:green'>✓ s2s_conversions table created</p>";
    
    echo "<h3>✅ S2S Setup Complete!</h3>";
    
    echo "<hr>";
    echo "<h3>How S2S Tracking Works:</h3>";
    echo "<ol>";
    echo "<li><strong>User clicks publisher link</strong> → Unique <code>click_id</code> generated</li>";
    echo "<li><strong>User redirected to advertiser site</strong> → <code>click_id</code> passed in URL</li>";
    echo "<li><strong>User converts on advertiser site</strong> → Advertiser captures <code>click_id</code></li>";
    echo "<li><strong>Advertiser server calls S2S postback</strong> → Conversion tracked to correct publisher</li>";
    echo "</ol>";
    
    echo "<h3>S2S Postback URL (Give to Advertiser):</h3>";
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $path = dirname($_SERVER['REQUEST_URI']);
    $postback_url = $base_url . $path . "/s2s_postback.php?click_id={click_id}";
    
    echo "<div style='background:#f5f5f5; padding:15px; border-radius:5px; margin:10px 0;'>";
    echo "<code style='word-break:break-all;'>" . htmlspecialchars($postback_url) . "</code>";
    echo "</div>";
    
    echo "<h4>Optional Parameters:</h4>";
    echo "<ul>";
    echo "<li><code>click_id</code> - Required - The click ID from URL</li>";
    echo "<li><code>txn_id</code> - Optional - Transaction/Order ID</li>";
    echo "<li><code>payout</code> - Optional - Conversion payout amount</li>";
    echo "<li><code>status</code> - Optional - approved/pending/rejected (default: approved)</li>";
    echo "</ul>";
    
    echo "<h4>Example Postback Call:</h4>";
    echo "<code>" . htmlspecialchars($base_url . $path . "/s2s_postback.php?click_id=abc123&txn_id=ORDER456&payout=10.00") . "</code>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
