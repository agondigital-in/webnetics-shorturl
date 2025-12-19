<?php
// s2s_postback.php - Server-to-Server Postback Endpoint
// Advertiser calls this URL from their server when conversion happens

header('Content-Type: application/json');

// Get parameters
$click_id = $_GET['click_id'] ?? $_POST['click_id'] ?? '';
$payout = $_GET['payout'] ?? $_POST['payout'] ?? null;
$transaction_id = $_GET['txn_id'] ?? $_POST['txn_id'] ?? '';
$status = $_GET['status'] ?? $_POST['status'] ?? 'approved';

// Validate click_id
if (empty($click_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'click_id is required']);
    exit;
}

try {
    require_once 'db_connection.php';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Find click record by click_id
    $stmt = $conn->prepare("
        SELECT c.*, camp.name as campaign_name, p.name as publisher_name
        FROM clicks c
        JOIN campaigns camp ON c.campaign_id = camp.id
        JOIN publishers p ON c.publisher_id = p.id
        WHERE c.click_id = ?
    ");
    $stmt->execute([$click_id]);
    $click = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$click) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invalid click_id']);
        exit;
    }
    
    // Check if already converted
    $stmt = $conn->prepare("SELECT id FROM s2s_conversions WHERE click_id = ?");
    $stmt->execute([$click_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Conversion already recorded', 'duplicate' => true]);
        exit;
    }
    
    // Record S2S conversion
    $stmt = $conn->prepare("
        INSERT INTO s2s_conversions (click_id, campaign_id, publisher_id, transaction_id, payout, status, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $click_id,
        $click['campaign_id'],
        $click['publisher_id'],
        $transaction_id,
        $payout,
        $status,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    // Update publisher pixel codes conversion count
    $stmt = $conn->prepare("
        UPDATE publisher_pixel_codes 
        SET conversion_count = conversion_count + 1 
        WHERE campaign_id = ? AND publisher_id = ?
    ");
    $stmt->execute([$click['campaign_id'], $click['publisher_id']]);
    
    // Update campaign conversion count
    $stmt = $conn->prepare("UPDATE campaigns SET conversion_count = conversion_count + 1 WHERE id = ?");
    $stmt->execute([$click['campaign_id']]);
    
    // Update daily conversions
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        INSERT INTO daily_conversions (campaign_id, conversion_date, conversions) 
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE conversions = conversions + 1
    ");
    $stmt->execute([$click['campaign_id'], $today]);
    
    // Log success
    error_log("S2S Conversion: click_id=$click_id, campaign={$click['campaign_name']}, publisher={$click['publisher_name']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversion recorded successfully',
        'data' => [
            'click_id' => $click_id,
            'campaign' => $click['campaign_name'],
            'publisher' => $click['publisher_name'],
            'transaction_id' => $transaction_id
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("S2S Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
