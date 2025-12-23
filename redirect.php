<?php
// redirect.php - Handles short URL redirects and tracks clicks with S2S support
// Now includes IP + Fingerprint based tracking for conversions without parameters
require_once 'db_connection.php';

// Generate unique click_id for S2S tracking
function generateClickId() {
    return bin2hex(random_bytes(16)) . time();
}

// Generate fingerprint from IP + User Agent for cookieless tracking
function generateFingerprint($ip, $user_agent) {
    return hash('sha256', $ip . '|' . $user_agent);
}

// Store click with fingerprint for parameter-less conversion tracking
// No duplicate IPs - updates existing record if same campaign+publisher+fingerprint
function storeClickWithFingerprint($conn, $campaign_id, $publisher_id, $click_id) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user_agent_hash = hash('sha256', $user_agent);
    $fingerprint = generateFingerprint($ip, $user_agent);
    
    try {
        // Check if click_fingerprints table exists, create if not
        $tableCheck = $conn->query("SHOW TABLES LIKE 'click_fingerprints'");
        if ($tableCheck->rowCount() == 0) {
            $conn->exec("CREATE TABLE IF NOT EXISTS `click_fingerprints` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `click_id` VARCHAR(64) NOT NULL,
                `campaign_id` INT NOT NULL,
                `publisher_id` INT NOT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `user_agent_hash` VARCHAR(64) NOT NULL,
                `fingerprint` VARCHAR(128) NOT NULL,
                `click_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `converted` BOOLEAN DEFAULT FALSE,
                `conversion_time` TIMESTAMP NULL,
                UNIQUE KEY `unique_fingerprint` (`campaign_id`, `publisher_id`, `fingerprint`),
                INDEX `idx_fingerprint` (`fingerprint`),
                INDEX `idx_click_time` (`click_time`)
            )");
        }
        
        // Delete old fingerprints (older than 48 hours) to keep DB clean
        $conn->exec("DELETE FROM click_fingerprints WHERE click_time < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        
        // Insert or update - no duplicate IPs stored
        $stmt = $conn->prepare("
            INSERT INTO click_fingerprints (click_id, campaign_id, publisher_id, ip_address, user_agent_hash, fingerprint, click_time, converted)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), FALSE)
            ON DUPLICATE KEY UPDATE 
                click_id = VALUES(click_id),
                ip_address = VALUES(ip_address),
                click_time = NOW(),
                converted = FALSE
        ");
        $stmt->execute([$click_id, $campaign_id, $publisher_id, $ip, $user_agent_hash, $fingerprint]);
        
    } catch (Exception $e) {
        error_log("Click fingerprint store error: " . $e->getMessage());
    }
}

// Store click and return click_id
function storeClick($conn, $campaign_id, $publisher_id, $click_id) {
    try {
        // Check if clicks table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'clicks'");
        if ($tableCheck->rowCount() > 0) {
            $stmt = $conn->prepare("
                INSERT INTO clicks (click_id, campaign_id, publisher_id, ip_address, user_agent, referrer)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $click_id,
                $campaign_id,
                $publisher_id,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['HTTP_REFERER'] ?? ''
            ]);
        }
        
        // Also store fingerprint for parameter-less tracking
        storeClickWithFingerprint($conn, $campaign_id, $publisher_id, $click_id);
        
    } catch (Exception $e) {
        error_log("Click store error: " . $e->getMessage());
    }
}

// Simple: target_url + _pubid + ?click_id=xyz
// Example: https://instagram.com/xyz → https://instagram.com/xyz_32?click_id=abc123
function appendTrackingParams($url, $click_id, $publisher_id) {
    // Remove trailing slash and add _publisher_id
    $url = rtrim($url, '/') . '_' . $publisher_id;
    // Add click_id as query parameter
    return $url . '?click_id=' . $click_id;
}

// Legacy function for backward compatibility
function appendClickId($url, $click_id, $publisher_id = null) {
    if ($publisher_id !== null) {
        return appendTrackingParams($url, $click_id, $publisher_id);
    }
    $separator = (parse_url($url, PHP_URL_QUERY) == null) ? '?' : '&';
    return $url . $separator . 'click_id=' . $click_id;
}

// Get the shortcode and publisher ID from the URL parameters
$short_code = $_GET['code'] ?? '';
$publisher_id = $_GET['pub'] ?? '';

// Debug logging
$debug_msg = date('Y-m-d H:i:s') . " - Request: " . $_SERVER['REQUEST_URI'] . " - Params: " . print_r($_GET, true);
file_put_contents('debug_log.txt', $debug_msg . "\n", FILE_APPEND);



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
            
            // Generate click_id for S2S tracking
            $click_id = generateClickId();
            storeClick($conn, $campaign_id, $publisher_id, $click_id);
            
            // Redirect to the target URL with click_id and publisher_id
            $redirect_url = appendTrackingParams($result['target_url'], $click_id, $publisher_id);
            header("Location: " . $redirect_url, true, 302);
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
            $debug_msg = date('Y-m-d H:i:s') . " - ERROR: Invalid link - code: {$short_code}, pub: {$publisher_id}\n";
            file_put_contents('debug_log.txt', $debug_msg, FILE_APPEND);
            http_response_code(404);
            die('Invalid link!');
        }
        
        $debug_msg = date('Y-m-d H:i:s') . " - SUCCESS: Found campaign, updating clicks\n";
        file_put_contents('debug_log.txt', $debug_msg, FILE_APPEND);
        
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
    
    // Generate click_id for S2S tracking
    $click_id = generateClickId();
    storeClick($conn, $campaign_id, $publisher_id, $click_id);
    
    // Redirect to the target URL with click_id and publisher_id
    $redirect_url = appendTrackingParams($result['target_url'], $click_id, $publisher_id);
    header("Location: " . $redirect_url, true, 302);
    exit();
    
} catch (PDOException $e) {
    http_response_code(500);
    die("Server Error: " . $e->getMessage());
}
?>