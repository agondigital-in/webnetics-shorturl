<?php
require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Campaign ID 47 - Click Data</h2>";
    
    // Check publisher_short_codes
    echo "<h3>publisher_short_codes table:</h3>";
    $stmt = $conn->prepare("SELECT * FROM publisher_short_codes WHERE campaign_id = 47");
    $stmt->execute();
    echo "<pre>" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
    
    // Check campaign_publishers
    echo "<h3>campaign_publishers table:</h3>";
    $stmt = $conn->prepare("SELECT * FROM campaign_publishers WHERE campaign_id = 47");
    $stmt->execute();
    echo "<pre>" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
    
    // Check publisher_daily_clicks
    echo "<h3>publisher_daily_clicks table:</h3>";
    $stmt = $conn->prepare("SELECT * FROM publisher_daily_clicks WHERE campaign_id = 47");
    $stmt->execute();
    echo "<pre>" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
    
    // Check campaigns
    echo "<h3>campaigns table:</h3>";
    $stmt = $conn->prepare("SELECT id, name, shortcode, click_count FROM campaigns WHERE id = 47");
    $stmt->execute();
    echo "<pre>" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
