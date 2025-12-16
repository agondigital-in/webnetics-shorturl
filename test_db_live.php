<?php
require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connection successful!";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
