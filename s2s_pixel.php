<?php
// s2s_pixel.php - S2S Pixel with Publisher-specific tracking
// Supports: 1) click_id based tracking 2) IP+Fingerprint based tracking (when no parameters)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get parameters
$pub_code = $_GET['pub'] ?? '';  // Publisher unique code
$click_id = $_GET['click_id'] ?? $_POST['click_id'] ?? '';  // Optional click_id
$txn_id = $_GET['txn_id'] ?? $_POST['txn_id'] ?? '';
$payout = $_GET['payout'] ?? $_POST['payout'] ?? null;
$status = $_GET['status'] ?? $_POST['status'] ?? 'approved';

// Validate pub_code
if (empty($pub_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'pub parameter is required']);
    exit;
}

try {
    require_once 'db_connection.php';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Decode pub_code to get campaign_id and publisher_id
    // Format: base64(campaign_id:publisher_id:secret)
    $decoded = base64_decode($pub_code);
    $parts = explode(':', $decoded);
    
    if (count($parts) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid pub code format']);
        exit;
    }
    
    $campaign_id = intval($parts[0]);
    $publisher_id = intval($parts[1]);
    
    // Verify campaign and publisher exist
    $stmt = $conn->prepare("
        SELECT c.name as campaign_name, p.name as publisher_name, 
               COALESCE(c.attribution_window, 24) as attribution_window
        FROM campaigns c
        JOIN campaign_publishers cp ON c.id = cp.campaign_id
        JOIN publishers p ON cp.publisher_id = p.id
        WHERE c.id = ? AND p.id = ? AND c.status = 'active'
    ");
    $stmt->execute([$campaign_id, $publisher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invalid campaign or publisher']);
        exit;
    }
    
    $attribution_window = $result['attribution_window'];
    $matched_click_id = null;
    $match_method = 'none';
    
    // METHOD 1: Try click_id based matching first (most accurate)
    if (!empty($click_id)) {
        $stmt = $conn->prepare("
            SELECT click_id FROM click_fingerprints 
            WHERE click_id = ? AND campaign_id = ? AND publisher_id = ? AND converted = FALSE
        ");
        $stmt->execute([$click_id, $campaign_id, $publisher_id]);
        if ($row = $stmt->fetch()) {
            $matched_click_id = $row['click_id'];
            $match_method = 'click_id';
        }
    }
    
    // METHOD 2: IP + Fingerprint based matching (when no click_id or click_id not found)
    if (empty($matched_click_id)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $fingerprint = hash('sha256', $ip . '|' . $user_agent);
        
        // Find matching click within attribution window
        $stmt = $conn->prepare("
            SELECT click_id, ip_address, click_time 
            FROM click_fingerprints 
            WHERE campaign_id = ? 
            AND publisher_id = ? 
            AND fingerprint = ?
            AND converted = FALSE
            AND click_time > DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY click_time DESC
            LIMIT 1
        ");
        $stmt->execute([$campaign_id, $publisher_id, $fingerprint, $attribution_window]);
        
        if ($row = $stmt->fetch()) {
            $matched_click_id = $row['click_id'];
            $match_method = 'fingerprint';
        }
    }
    
    // METHOD 3: IP-only matching as fallback (less accurate but works)
    if (empty($matched_click_id)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $conn->prepare("
            SELECT click_id, click_time 
            FROM click_fingerprints 
            WHERE campaign_id = ? 
            AND publisher_id = ? 
            AND ip_address = ?
            AND converted = FALSE
            AND click_time > DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY click_time DESC
            LIMIT 1
        ");
        $stmt->execute([$campaign_id, $publisher_id, $ip, $attribution_window]);
        
        if ($row = $stmt->fetch()) {
            $matched_click_id = $row['click_id'];
            $match_method = 'ip_only';
        }
    }
    
    // Check for duplicate conversion
    if (!empty($txn_id)) {
        $stmt = $conn->prepare("
            SELECT id FROM s2s_conversions 
            WHERE campaign_id = ? AND publisher_id = ? AND transaction_id = ?
        ");
        $stmt->execute([$campaign_id, $publisher_id, $txn_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => true, 'message' => 'Conversion already recorded', 'duplicate' => true]);
            exit;
        }
    }
    
    // If no click matched, still record conversion but mark as unmatched
    if (empty($matched_click_id)) {
        $matched_click_id = 'UNMATCHED_' . bin2hex(random_bytes(8)) . '_' . time();
        $match_method = 'unmatched';
    }
    
    // Mark click as converted in fingerprints table
    if ($match_method !== 'unmatched') {
        $stmt = $conn->prepare("
            UPDATE click_fingerprints 
            SET converted = TRUE, conversion_time = NOW() 
            WHERE click_id = ?
        ");
        $stmt->execute([$matched_click_id]);
    }
    
    // Record S2S conversion
    $stmt = $conn->prepare("
        INSERT INTO s2s_conversions (click_id, campaign_id, publisher_id, transaction_id, payout, status, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $matched_click_id,
        $campaign_id,
        $publisher_id,
        $txn_id,
        $payout,
        $status,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    // Update publisher pixel codes conversion count
    try {
        $stmt = $conn->prepare("
            UPDATE publisher_pixel_codes 
            SET conversion_count = conversion_count + 1 
            WHERE campaign_id = ? AND publisher_id = ?
        ");
        $stmt->execute([$campaign_id, $publisher_id]);
    } catch (Exception $e) {
        // Table might not exist
    }
    
    // Update campaign conversion count
    $stmt = $conn->prepare("UPDATE campaigns SET conversion_count = conversion_count + 1 WHERE id = ?");
    $stmt->execute([$campaign_id]);
    
    // Update daily conversions
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        INSERT INTO daily_conversions (campaign_id, conversion_date, conversions) 
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE conversions = conversions + 1
    ");
    $stmt->execute([$campaign_id, $today]);
    
    // Log success
    error_log("S2S Conversion: campaign={$result['campaign_name']}, publisher={$result['publisher_name']}, method=$match_method, txn=$txn_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversion recorded successfully',
        'match_method' => $match_method,
        'data' => [
            'campaign' => $result['campaign_name'],
            'publisher' => $result['publisher_name'],
            'transaction_id' => $txn_id,
            'attribution_window' => $attribution_window . ' hours'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("S2S Pixel Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
