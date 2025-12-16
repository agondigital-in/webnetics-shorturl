<?php
// redirect_debug.php - Debug version of redirect with detailed logging
require_once 'db_connection.php';

// Get the shortcode and publisher ID from the URL parameters
$short_code = $_GET['code'] ?? '';
$publisher_id = $_GET['pub'] ?? '';

// Debug logging
$debug = [];
$debug[] = "=== " . date('Y-m-d H:i:s') . " ===";
$debug[] = "Request URI: " . $_SERVER['REQUEST_URI'];
$debug[] = "Short Code: " . $short_code;
$debug[] = "Publisher ID: " . $publisher_id;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $debug[] = "✓ Database connected";
    
    // Check if this is a publisher-specific short code
    $stmt = $conn->prepare("
        SELECT c.id as campaign_id, c.target_url, psc.publisher_id, psc.short_code, psc.clicks
        FROM publisher_short_codes psc
        JOIN campaigns c ON psc.campaign_id = c.id
        WHERE psc.short_code = ? AND psc.publisher_id = ? AND c.status = 'active'
    ");
    $stmt->execute([$short_code, $publisher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $debug[] = "✓ Found campaign: ID={$result['campaign_id']}, Current clicks={$result['clicks']}";
        
        $campaign_id = $result['campaign_id'];
        $target_url = $result['target_url'];
        
        // Update click count for publisher-specific short code
        $stmt = $conn->prepare("UPDATE publisher_short_codes SET clicks = clicks + 1 WHERE short_code = ? AND publisher_id = ?");
        $stmt->execute([$short_code, $publisher_id]);
        $debug[] = "✓ Updated publisher_short_codes: " . $stmt->rowCount() . " rows";
        
        // Update total click count for the campaign
        $stmt = $conn->prepare("UPDATE campaigns SET click_count = click_count + 1 WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $debug[] = "✓ Updated campaigns: " . $stmt->rowCount() . " rows";
        
        // Track daily clicks
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            INSERT INTO publisher_daily_clicks (campaign_id, publisher_id, click_date, clicks)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE clicks = clicks + 1
        ");
        $stmt->execute([$campaign_id, $publisher_id, $today]);
        $debug[] = "✓ Updated daily clicks";
        
        // Verify the update
        $stmt = $conn->prepare("SELECT clicks FROM publisher_short_codes WHERE short_code = ? AND publisher_id = ?");
        $stmt->execute([$short_code, $publisher_id]);
        $new_clicks = $stmt->fetchColumn();
        $debug[] = "✓ New click count: " . $new_clicks;
        
        $debug[] = "✓ Redirecting to: " . $target_url;
        
        // Save debug log
        file_put_contents('debug_log.txt', implode("\n", $debug) . "\n\n", FILE_APPEND);
        
        // Redirect to the target URL
        header("Location: " . $target_url, true, 302);
        exit();
    } else {
        $debug[] = "✗ No campaign found!";
        
        // Check what's in the database
        $stmt = $conn->prepare("SELECT short_code, publisher_id, campaign_id FROM publisher_short_codes WHERE short_code = ?");
        $stmt->execute([$short_code]);
        $all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($all_matches)) {
            $debug[] = "✗ Short code '{$short_code}' does not exist in database";
        } else {
            $debug[] = "✓ Found short code, but publisher mismatch:";
            foreach ($all_matches as $match) {
                $debug[] = "  - Publisher ID: {$match['publisher_id']}, Campaign ID: {$match['campaign_id']}";
            }
        }
        
        file_put_contents('debug_log.txt', implode("\n", $debug) . "\n\n", FILE_APPEND);
        
        http_response_code(404);
        die('Invalid link! Check debug_log.txt for details.');
    }
    
} catch (PDOException $e) {
    $debug[] = "✗ Database Error: " . $e->getMessage();
    file_put_contents('debug_log.txt', implode("\n", $debug) . "\n\n", FILE_APPEND);
    
    http_response_code(500);
    die("Server Error: " . $e->getMessage());
}
?>
