<?php
// pixel.php - Conversion Tracking Pixel Endpoint
// This file serves a 1x1 transparent pixel and tracks conversions

// Function to output pixel and exit
function outputPixel() {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit();
}

// Get pixel code from URL
$pixel_code = $_GET['p'] ?? '';

if (empty($pixel_code)) {
    outputPixel();
}

try {
    require_once 'db_connection.php';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if pixel_code column exists
    $check = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'pixel_code'");
    if ($check->rowCount() == 0) {
        // pixel_code column doesn't exist, just output pixel
        outputPixel();
    }
    
    $campaign_id = null;
    $publisher_id = null;
    
    // First check publisher_pixel_codes table (publisher-wise tracking)
    $pubPixelCheck = $conn->query("SHOW TABLES LIKE 'publisher_pixel_codes'");
    if ($pubPixelCheck->rowCount() > 0) {
        $stmt = $conn->prepare("
            SELECT ppc.campaign_id, ppc.publisher_id, c.status 
            FROM publisher_pixel_codes ppc 
            JOIN campaigns c ON ppc.campaign_id = c.id 
            WHERE ppc.pixel_code = ? AND c.status = 'active'
        ");
        $stmt->execute([$pixel_code]);
        $pubPixel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pubPixel) {
            $campaign_id = $pubPixel['campaign_id'];
            $publisher_id = $pubPixel['publisher_id'];
        }
    }
    
    // If not found in publisher pixels, check campaign pixel_code
    if (!$campaign_id) {
        $stmt = $conn->prepare("SELECT id, status FROM campaigns WHERE pixel_code = ? AND status = 'active'");
        $stmt->execute([$pixel_code]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($campaign) {
            $campaign_id = $campaign['id'];
        }
    }
    
    if ($campaign_id) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $today = date('Y-m-d');
        
        // Check if conversions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'conversions'");
        if ($tableCheck->rowCount() > 0) {
            // Record conversion with publisher_id if available
            if ($publisher_id) {
                // Check if publisher_id column exists
                $colCheck = $conn->query("SHOW COLUMNS FROM conversions LIKE 'publisher_id'");
                if ($colCheck->rowCount() > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO conversions (campaign_id, publisher_id, pixel_code, ip_address, user_agent, referrer) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$campaign_id, $publisher_id, $pixel_code, $ip_address, $user_agent, $referrer]);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO conversions (campaign_id, pixel_code, ip_address, user_agent, referrer) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$campaign_id, $pixel_code, $ip_address, $user_agent, $referrer]);
                }
                
                // Update publisher pixel conversion count
                $stmt = $conn->prepare("UPDATE publisher_pixel_codes SET conversion_count = conversion_count + 1 WHERE campaign_id = ? AND publisher_id = ?");
                $stmt->execute([$campaign_id, $publisher_id]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO conversions (campaign_id, pixel_code, ip_address, user_agent, referrer) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$campaign_id, $pixel_code, $ip_address, $user_agent, $referrer]);
            }
            
            // Update campaign conversion count (if column exists)
            try {
                $stmt = $conn->prepare("UPDATE campaigns SET conversion_count = conversion_count + 1 WHERE id = ?");
                $stmt->execute([$campaign_id]);
            } catch (Exception $e) {
                // Column doesn't exist, skip
            }
            
            // Update daily conversions (if table exists)
            $dailyCheck = $conn->query("SHOW TABLES LIKE 'daily_conversions'");
            if ($dailyCheck->rowCount() > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO daily_conversions (campaign_id, conversion_date, conversions) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE conversions = conversions + 1
                ");
                $stmt->execute([$campaign_id, $today]);
            }
        }
    }
} catch (Exception $e) {
    // Silently fail - don't expose errors
    error_log("Pixel tracking error: " . $e->getMessage());
}

// Return 1x1 transparent GIF
outputPixel();
