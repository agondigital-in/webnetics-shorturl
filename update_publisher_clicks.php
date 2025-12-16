<?php
// update_publisher_clicks.php - Add clicks column to publisher_short_codes table
require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Add clicks column to publisher_short_codes table if it doesn't exist
    $sql = "ALTER TABLE `publisher_short_codes` ADD COLUMN `clicks` INT DEFAULT 0";
    $conn->exec($sql);
    echo "Column 'clicks' added successfully to 'publisher_short_codes' table.\n";
    
    echo "Database update completed successfully!\n";
    
} catch (PDOException $e) {
    // Check if the error is because the column already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'clicks' already exists in 'publisher_short_codes' table.\n";
        echo "Database is already up to date.\n";
    } else {
        die("Database update failed: " . $e->getMessage());
    }
}
?>