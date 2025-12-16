<?php
// update_database.php - Update database schema for publisher-specific short codes
require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create publisher_short_codes table
    $sql = "CREATE TABLE IF NOT EXISTS `publisher_short_codes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `publisher_id` INT NOT NULL,
        `short_code` VARCHAR(20) UNIQUE NOT NULL,
        `clicks` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_campaign_publisher_code` (`campaign_id`, `publisher_id`)
    )";
    $conn->exec($sql);
    echo "Table 'publisher_short_codes' created successfully.\n";
    
    echo "Database update completed successfully!\n";
    
} catch (PDOException $e) {
    die("Database update failed: " . $e->getMessage());
}
?>