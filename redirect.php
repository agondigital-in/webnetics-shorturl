<?php
// redirect.php - Handles short URL redirects and tracks clicks
require_once 'db_connection.php';

// Get the shortcode and publisher ID from the URL parameters
$short_code = $_GET['code'] ?? '';
$publisher_id = $_GET['pub'] ?? '';

// If no publisher ID, check if this is a publisher-specific short code
if (empty($publisher_id) && !empty($short_code)) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Check if this is a publisher-specific short code
        $stmt = $conn->prepare("
            SELECT c.target_url, psc.publisher_id, psc.campaign_id
            FROM publisher_short_codes psc
            JOIN campaigns c ON psc.campaign_id = c.id
            WHERE psc.short_code = ? AND c.status = 'active'
        ");
        $stmt->execute([$short_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $publisher_id = $result['publisher_id'];
            $campaign_id = $result['campaign_id'];
            
            // Update click count for this publisher's link
            $stmt = $conn->prepare("UPDATE publisher_short_codes SET clicks = clicks + 1 WHERE short_code = ?");
            $stmt->execute([$short_code]);
            
            // Update total click count for the campaign
            $stmt = $conn->prepare("UPDATE campaigns SET click_count = click_count + 1 WHERE id = ?");
            $stmt->execute([$campaign_id]);
            
            // Track daily clicks
            $today = date('Y-m-d');
            $stmt = $conn->prepare("
                INSERT INTO publisher_daily_clicks (campaign_id, publisher_id, click_date, clicks)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE clicks = clicks + 1
            ");
            $stmt->execute([$campaign_id, $publisher_id, $today]);
            
            // Redirect to the target URL
            header("Location: " . $result['target_url'], true, 302);
            exit();
        }
    } catch (PDOException $e) {
        http_response_code(500);
        die("Server Error: " . $e->getMessage());
    }
}

// Handle the standard format with publisher ID parameter
if (empty($short_code) || empty($publisher_id)) {
    http_response_code(400);
    die('Bad Request: Missing parameters');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Find the campaign and publisher details
    $stmt = $conn->prepare("
        SELECT c.target_url, cp.id as campaign_publisher_id
        FROM campaigns c 
        JOIN campaign_publishers cp ON c.id = cp.campaign_id 
        WHERE c.shortcode = ? AND cp.publisher_id = ? AND c.status = 'active'
    ");
    $stmt->execute([$short_code, $publisher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // Check if this is a publisher-specific short code
        $stmt = $conn->prepare("
            SELECT c.target_url, psc.id as publisher_short_code_id
            FROM publisher_short_codes psc
            JOIN campaigns c ON psc.campaign_id = c.id
            WHERE psc.short_code = ? AND psc.publisher_id = ? AND c.status = 'active'
        ");
        $stmt->execute([$short_code, $publisher_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            http_response_code(404);
            die('Invalid link!');
        }
        
        // Get campaign_id for daily tracking
        $stmt = $conn->prepare("SELECT campaign_id FROM publisher_short_codes WHERE short_code = ? AND publisher_id = ?");
        $stmt->execute([$short_code, $publisher_id]);
        $campaign_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $campaign_id = $campaign_data['campaign_id'];
        
        // Update click count for publisher-specific short code
        $stmt = $conn->prepare("UPDATE publisher_short_codes SET clicks = clicks + 1 WHERE short_code = ? AND publisher_id = ?");
        $stmt->execute([$short_code, $publisher_id]);
        
        // Update total click count for the campaign
        $stmt = $conn->prepare("UPDATE campaigns SET click_count = click_count + 1 WHERE id = ?");
        $stmt->execute([$campaign_id]);
        
        // Track daily clicks
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            INSERT INTO publisher_daily_clicks (campaign_id, publisher_id, click_date, clicks)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE clicks = clicks + 1
        ");
        $stmt->execute([$campaign_id, $publisher_id, $today]);
    } else {
        // Get campaign_id for daily tracking
        $stmt = $conn->prepare("SELECT campaign_id FROM campaign_publishers WHERE id = ?");
        $stmt->execute([$result['campaign_publisher_id']]);
        $campaign_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $campaign_id = $campaign_data['campaign_id'];
        
        // Update click count for campaign_publisher link
        $stmt = $conn->prepare("UPDATE campaign_publishers SET clicks = clicks + 1 WHERE id = ?");
        $stmt->execute([$result['campaign_publisher_id']]);
        
        // Update total click count for the campaign
        $stmt = $conn->prepare("UPDATE campaigns SET click_count = click_count + 1 WHERE id = ?");
        $stmt->execute([$campaign_id]);
        
        // Track daily clicks
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            INSERT INTO publisher_daily_clicks (campaign_id, publisher_id, click_date, clicks)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE clicks = clicks + 1
        ");
        $stmt->execute([$campaign_id, $publisher_id, $today]);
    }
    
    // Check if campaign is within date range
    $stmt = $conn->prepare("SELECT start_date, end_date FROM campaigns WHERE shortcode = ?");
    $stmt->execute([$short_code]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($campaign) {
        $current_date = date('Y-m-d');
        if ($current_date < $campaign['start_date'] || $current_date > $campaign['end_date']) {
            http_response_code(404);
            die('Campaign is not active on this date');
        }
    }
    
    // Redirect to the target URL
    header("Location: " . $result['target_url'], true, 302);
    exit();
    
} catch (PDOException $e) {
    http_response_code(500);
    die("Server Error: " . $e->getMessage());
}
?>