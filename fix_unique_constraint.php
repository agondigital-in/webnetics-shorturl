<?php
// fix_unique_constraint.php - Remove UNIQUE constraint from publisher_short_codes.short_code
require_once 'db_connection.php';

header('Content-Type: text/html; charset=utf-8');

// Auto-fix mode
$auto_fix = isset($_GET['auto']) && $_GET['auto'] == '1';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Database Constraint</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 15px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 15px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 15px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; padding: 15px; background: #fff3cd; border-radius: 5px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        h1 { color: #333; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="container">
<h1>🔧 Fix Database Constraint</h1>

<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div class='info'><strong>Step 1:</strong> Checking current table structure...</div>";
    
    // Check if UNIQUE constraint exists
    $stmt = $conn->prepare("SHOW INDEX FROM publisher_short_codes WHERE Column_name = 'short_code'");
    $stmt->execute();
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_unique = false;
    $index_name = '';
    
    foreach ($indexes as $index) {
        if ($index['Non_unique'] == 0 && $index['Key_name'] != 'PRIMARY') {
            $has_unique = true;
            $index_name = $index['Key_name'];
            break;
        }
    }
    
    if ($has_unique) {
        echo "<div class='warning'><strong>⚠️ Found UNIQUE constraint:</strong> <code>{$index_name}</code> on <code>short_code</code> column</div>";
        echo "<div class='info'>This constraint prevents multiple publishers from using the same campaign code.</div>";
        
        if ($auto_fix) {
            echo "<div class='info'><strong>Step 2:</strong> Removing UNIQUE constraint...</div>";
            
            try {
                // Drop the UNIQUE constraint
                $sql = "ALTER TABLE publisher_short_codes DROP INDEX `{$index_name}`";
                $conn->exec($sql);
                
                echo "<div class='success'>✅ <strong>Success!</strong> UNIQUE constraint removed from <code>short_code</code> column</div>";
                
                echo "<div class='info'><strong>Step 3:</strong> Verifying changes...</div>";
                
                // Verify the change
                $stmt = $conn->prepare("SHOW INDEX FROM publisher_short_codes WHERE Column_name = 'short_code'");
                $stmt->execute();
                $indexes_after = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $still_unique = false;
                foreach ($indexes_after as $index) {
                    if ($index['Non_unique'] == 0 && $index['Key_name'] != 'PRIMARY') {
                        $still_unique = true;
                        break;
                    }
                }
                
                if (!$still_unique) {
                    echo "<div class='success'>✅ <strong>Verified!</strong> The <code>short_code</code> column is no longer UNIQUE</div>";
                    echo "<div class='success'><strong>Now multiple publishers can use the same campaign short code!</strong></div>";
                    echo "<p><a href='fix_duplicate_shortcodes.php' class='btn'>→ Next: Run Shortcode Cleanup</a></p>";
                } else {
                    echo "<div class='error'>⚠️ Warning: UNIQUE constraint may still exist. Please check manually.</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'><strong>Error removing constraint:</strong> " . $e->getMessage() . "</div>";
                echo "<div class='info'>You may need to run this SQL manually in phpMyAdmin:</div>";
                echo "<div class='info'><code>ALTER TABLE publisher_short_codes DROP INDEX `{$index_name}`;</code></div>";
            }
        } else {
            echo "<div class='warning'><strong>Action Required:</strong> Click the button below to automatically fix this issue.</div>";
            echo "<p><a href='?auto=1' class='btn'>🔧 Fix Now (Remove UNIQUE Constraint)</a></p>";
            echo "<hr>";
            echo "<div class='info'><strong>Or run this SQL manually in phpMyAdmin:</strong></div>";
            echo "<div class='info'><code>ALTER TABLE publisher_short_codes DROP INDEX `{$index_name}`;</code></div>";
        }
        
    } else {
        echo "<div class='success'>✅ <strong>Good news!</strong> The <code>short_code</code> column is already NOT UNIQUE</div>";
        echo "<div class='info'>No changes needed. Multiple publishers can already use the same campaign short code.</div>";
        echo "<p><a href='fix_duplicate_shortcodes.php' class='btn'>→ Next: Run Shortcode Cleanup</a></p>";
    }
    
    echo "<hr>";
    echo "<h2>Current Table Structure</h2>";
    echo "<div class='info'>";
    echo "<strong>Indexes on publisher_short_codes table:</strong><br><br>";
    
    $stmt = $conn->prepare("SHOW INDEX FROM publisher_short_codes");
    $stmt->execute();
    $all_indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_indexes)) {
        echo "No indexes found.";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Key Name</th><th>Column</th><th>Unique</th></tr>";
        foreach ($all_indexes as $idx) {
            $unique_text = $idx['Non_unique'] == 0 ? 'YES' : 'NO';
            echo "<tr><td>{$idx['Key_name']}</td><td>{$idx['Column_name']}</td><td>{$unique_text}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}
?>

</div>
</body>
</html>
