<?php
// test_daily_clicks.php - Test daily click tracking
require_once 'db_connection.php';

echo "<h2>Testing Daily Click Tracking</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if table exists
    echo "<h3>1. Checking if table exists...</h3>";
    $stmt = $conn->query("SHOW TABLES LIKE 'publisher_daily_clicks'");
    $table_exists = $stmt->fetch();
    if ($table_exists) {
        echo "✅ Table 'publisher_daily_clicks' exists<br>";
    } else {
        echo "❌ Table 'publisher_daily_clicks' does NOT exist<br>";
        die("Please run update_daily_clicks_table.php first");
    }
    
    // Check table structure
    echo "<h3>2. Table Structure:</h3>";
    $stmt = $conn->query("DESCRIBE publisher_daily_clicks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check existing data
    echo "<h3>3. Current Data in Table:</h3>";
    $stmt = $conn->query("SELECT * FROM publisher_daily_clicks ORDER BY click_date DESC LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($data) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Campaign ID</th><th>Publisher ID</th><th>Click Date</th><th>Clicks</th><th>Created At</th></tr>";
        foreach ($data as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['campaign_id']}</td>";
            echo "<td>{$row['publisher_id']}</td>";
            echo "<td>{$row['click_date']}</td>";
            echo "<td>{$row['clicks']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data found in table yet.<br>";
    }
    
    // Get actual publisher IDs from publisher_short_codes
    echo "<h3>4. Getting Actual Publisher IDs...</h3>";
    $stmt = $conn->query("SELECT publisher_id FROM publisher_short_codes WHERE campaign_id = 33 LIMIT 1");
    $pub_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pub_data) {
        echo "❌ No publishers found for campaign 33<br>";
        die();
    }
    
    // Test insert
    echo "<h3>5. Testing Insert...</h3>";
    $test_campaign_id = 33; // Change this to your campaign ID
    $test_publisher_id = $pub_data['publisher_id']; // Use actual publisher ID
    $today = date('Y-m-d');
    
    echo "Attempting to insert: Campaign ID = $test_campaign_id, Publisher ID = $test_publisher_id, Date = $today<br>";
    
    $stmt = $conn->prepare("
        INSERT INTO publisher_daily_clicks (campaign_id, publisher_id, click_date, clicks)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE clicks = clicks + 1
    ");
    $result = $stmt->execute([$test_campaign_id, $test_publisher_id, $today]);
    
    if ($result) {
        echo "✅ Insert/Update successful!<br>";
        echo "Affected rows: " . $stmt->rowCount() . "<br>";
    } else {
        echo "❌ Insert/Update failed!<br>";
    }
    
    // Check data again
    echo "<h3>6. Data After Test Insert:</h3>";
    $stmt = $conn->query("SELECT * FROM publisher_daily_clicks ORDER BY click_date DESC LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Campaign ID</th><th>Publisher ID</th><th>Click Date</th><th>Clicks</th><th>Created At</th></tr>";
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['campaign_id']}</td>";
        echo "<td>{$row['publisher_id']}</td>";
        echo "<td>{$row['click_date']}</td>";
        echo "<td>{$row['clicks']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check publisher_short_codes table
    echo "<h3>7. Publisher Short Codes:</h3>";
    $stmt = $conn->query("SELECT * FROM publisher_short_codes LIMIT 10");
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($codes) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Campaign ID</th><th>Publisher ID</th><th>Short Code</th><th>Clicks</th></tr>";
        foreach ($codes as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['campaign_id']}</td>";
            echo "<td>{$row['publisher_id']}</td>";
            echo "<td>{$row['short_code']}</td>";
            echo "<td>{$row['clicks']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No publisher short codes found.<br>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
