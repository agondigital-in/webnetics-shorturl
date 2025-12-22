<?php
// pixel.php - Conversion Tracking Pixel Endpoint
// This file serves a 1x1 transparent pixel and tracks conversions
// NOW SUPPORTS: Fingerprint-based tracking (no parameters needed!)

// Function to output pixel and exit
function outputPixel() {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit();
}

// Generate fingerprint from IP + User Agent (same as redirect.php)
function generateFingerprint($ip, $user_agent) {
    return hash('sha256', $ip . '|' . $user_agent);
}

// Match conversion with click using fingerprint (NO PARAMETERS NEEDED!)
function matchConversionByFingerprint($conn, $campaign_id) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fingerprint = generateFingerprint($ip, $user_agent);
    
    try {
        // Check if click_fingerprints table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'click_fingerprints'");
        if ($tableCheck->rowCount() == 0) {
            return null; // Table doesn't exist
        }
        
        // Get attribution window (default 24 hours)
        $attribution_window = 24;
        $attrCheck = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'attribution_window'");
        if ($attrCheck->rowCount() > 0) {
            $stmt = $conn->prepare("SELECT attribution_window FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['attribution_window']) {
                $attribution_window = $result['attribution_window'];
            }
        }
        
        // Find matching click within attribution window
        $stmt = $conn->prepare("
            SELECT id, publisher_id, click_id, click_time 
            FROM click_fingerprints 
            WHERE campaign_id = ? 
            AND fingerprint = ? 
            AND converted = FALSE
            AND click_time >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY click_time DESC 
            LIMIT 1
        ");
        $stmt->execute([$campaign_id, $fingerprint, $attribution_window]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($match) {
            // Mark as converted
            $stmt = $conn->prepare("
                UPDATE click_fingerprints 
                SET converted = TRUE, conversion_time = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$match['id']]);
            
            return $match; // Return matched click data
        }
        
    } catch (Exception $e) {
        error_log("Fingerprint matching error: " . $e->getMessage());
    }
    
    return null;
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
        
        // TRY FINGERPRINT MATCHING FIRST (works without parameters!)
        $fingerprintMatch = matchConversionByFingerprint($conn, $campaign_id);
        
        // If fingerprint matched, use that publisher_id
        if ($fingerprintMatch && !$publisher_id) {
            $publisher_id = $fingerprintMatch['publisher_id'];
        }
        
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
                
                // Update publisher daily conversions
                $dailyPubCheck = $conn->query("SHOW TABLES LIKE 'publisher_daily_clicks'");
                if ($dailyPubCheck->rowCount() > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO publisher_daily_clicks (campaign_id, publisher_id, click_date, conversions) 
                        VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE conversions = conversions + 1
                    ");
                    $stmt->execute([$campaign_id, $publisher_id, $today]);
                }
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
