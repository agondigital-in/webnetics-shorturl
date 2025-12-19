<?php
// setup_publisher_pixels.php - Create publisher_pixel_codes table
require_once 'db_connection.php';

echo "<h2>Setup Publisher Pixel Codes Table</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create publisher_pixel_codes table
    $sql = "CREATE TABLE IF NOT EXISTS `publisher_pixel_codes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `publisher_id` INT NOT NULL,
        `pixel_code` VARCHAR(50) NOT NULL UNIQUE,
        `conversion_count` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_pixel_code` (`pixel_code`),
        INDEX `idx_campaign_publisher` (`campaign_id`, `publisher_id`)
    )";
    
    $conn->exec($sql);
    echo "<p style='color:green'>✓ publisher_pixel_codes table created successfully!</p>";
    
    // Add publisher_id column to conversions table if not exists
    $check = $conn->query("SHOW COLUMNS FROM conversions LIKE 'publisher_id'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE `conversions` ADD COLUMN `publisher_id` INT NULL AFTER `campaign_id`");
        echo "<p style='color:green'>✓ publisher_id column added to conversions table!</p>";
    } else {
        echo "<p style='color:blue'>ℹ publisher_id column already exists in conversions table</p>";
    }
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $stmt = $conn->query("DESCRIBE publisher_pixel_codes");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>Now create a new campaign and each publisher will get their own pixel code.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
