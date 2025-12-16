<?php
// fix_duplicate_shortcodes.php - Fix duplicate short codes for campaigns
// This script will update all publishers in a campaign to use the same campaign shortcode

require_once 'db_connection.php';

// Set content type for browser display
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Duplicate Shortcodes</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: green; }
        .info { color: blue; }
        .campaign { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .publisher { margin-left: 20px; color: #666; }
        h1 { color: #333; }
        .summary { background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔧 Shortcode Cleanup Tool</h1>
<p>Starting shortcode cleanup...</p>
<hr>
<?php

echo "<p class='info'>Starting shortcode cleanup...</p>\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all campaigns
    $stmt = $conn->prepare("SELECT id, shortcode, name FROM campaigns ORDER BY id");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_updated = 0;
    
    foreach ($campaigns as $campaign) {
        $campaign_id = $campaign['id'];
        $campaign_shortcode = $campaign['shortcode'];
        $campaign_name = $campaign['name'];
        
        echo "<div class='campaign'>";
        echo "<strong>Processing Campaign:</strong> {$campaign_name} (ID: {$campaign_id}, Code: <code>{$campaign_shortcode}</code>)<br>";
        
        // Update all publisher_short_codes for this campaign to use the campaign's main shortcode
        $stmt = $conn->prepare("
            UPDATE publisher_short_codes 
            SET short_code = ? 
            WHERE campaign_id = ? AND short_code != ?
        ");
        $stmt->execute([$campaign_shortcode, $campaign_id, $campaign_shortcode]);
        
        $updated_count = $stmt->rowCount();
        $total_updated += $updated_count;
        
        if ($updated_count > 0) {
            echo "<span class='success'>✓ Updated {$updated_count} publisher short codes to use: <code>{$campaign_shortcode}</code></span><br>";
        } else {
            echo "<span class='success'>✓ All publishers already using correct code</span><br>";
        }
        
        // Show current publisher assignments
        $stmt = $conn->prepare("
            SELECT p.name, psc.short_code 
            FROM publisher_short_codes psc
            JOIN publishers p ON psc.publisher_id = p.id
            WHERE psc.campaign_id = ?
        ");
        $stmt->execute([$campaign_id]);
        $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($publishers as $pub) {
            echo "<div class='publisher'>→ Publisher: <strong>{$pub['name']}</strong> → Code: <code>{$pub['short_code']}</code></div>";
        }
        
        echo "</div>";
    }
    
    echo "<div class='summary'>";
    echo "<h2>✅ Cleanup Complete!</h2>";
    echo "<p><strong>Total campaigns processed:</strong> " . count($campaigns) . "</p>";
    echo "<p><strong>Total publisher codes updated:</strong> {$total_updated}</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
</div>
</body>
</html>
