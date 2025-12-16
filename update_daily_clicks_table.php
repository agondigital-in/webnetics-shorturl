<?php
// update_daily_clicks_table.php - Create table for daily click tracking
require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create publisher_daily_clicks table
    $sql = "CREATE TABLE IF NOT EXISTS `publisher_daily_clicks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `publisher_id` INT NOT NULL,
        `click_date` DATE NOT NULL,
        `clicks` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_publisher_campaign_date` (`campaign_id`, `publisher_id`, `click_date`)
    )";
    $conn->exec($sql);
    echo "Table 'publisher_daily_clicks' created successfully.\n";
    
    // Add index for faster queries
    $sql = "CREATE INDEX idx_click_date ON publisher_daily_clicks(click_date)";
    try {
        $conn->exec($sql);
        echo "Index on click_date created successfully.\n";
    } catch (PDOException $e) {
        // Index might already exist
        echo "Index might already exist: " . $e->getMessage() . "\n";
    }
    
    echo "Database update completed successfully!\n";
    
} catch (PDOException $e) {
    die("Database update failed: " . $e->getMessage());
}
?>
