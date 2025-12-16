<?php
// install.php - Database installation script

// Load environment variables with proper path resolution
$env_file = __DIR__ . '/.env';

// Check if .env file exists
if (!file_exists($env_file)) {
    die("Environment file not found. Please create .env file with database configuration.");
}

// Parse the .env file
$env = parse_ini_file($env_file);

// Check if parse_ini_file succeeded
if ($env === false) {
    die("Failed to parse environment file.");
}

// Database configuration with defaults
$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? '3308';
$dbname = $env['DB_DATABASE'] ?? 'default';
$username = $env['DB_USERNAME'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "Database '$dbname' created or already exists.\n";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('super_admin', 'admin', 'publisher') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'users' created.\n";
    
    // Create advertisers table
    $sql = "CREATE TABLE IF NOT EXISTS `advertisers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `company` VARCHAR(100),
        `phone` VARCHAR(20),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'advertisers' created.\n";
    
    // Create publishers table
    $sql = "CREATE TABLE IF NOT EXISTS `publishers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `website` VARCHAR(255),
        `phone` VARCHAR(20),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'publishers' created.\n";
    
    // Create campaigns table
    $sql = "CREATE TABLE IF NOT EXISTS `campaigns` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `shortcode` VARCHAR(20) UNIQUE NOT NULL,
        `target_url` TEXT NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `advertiser_payout` DECIMAL(10, 2) DEFAULT 0.00,
        `publisher_payout` DECIMAL(10, 2) DEFAULT 0.00,
        `campaign_type` ENUM('CPR', 'CPL', 'CPC', 'CPM', 'CPS', 'None') DEFAULT 'None',
        `target_leads` INT DEFAULT 0,
        `validated_leads` INT DEFAULT 0,
        `click_count` INT DEFAULT 0,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `payment_status` ENUM('pending', 'completed') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'campaigns' created.\n";
    
    // Create campaign_advertisers junction table
    $sql = "CREATE TABLE IF NOT EXISTS `campaign_advertisers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `advertiser_id` INT NOT NULL,
        `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`advertiser_id`) REFERENCES `advertisers`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_campaign_advertiser` (`campaign_id`, `advertiser_id`)
    )";
    $pdo->exec($sql);
    echo "Table 'campaign_advertisers' created.\n";
    
    // Create campaign_publishers junction table
    $sql = "CREATE TABLE IF NOT EXISTS `campaign_publishers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `publisher_id` INT NOT NULL,
        `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_campaign_publisher` (`campaign_id`, `publisher_id`)
    )";
    $pdo->exec($sql);
    echo "Table 'campaign_publishers' created.\n";
    
    // Create default super admin user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
    $stmt->execute(['admin']);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $hashedPassword = password_hash('Agondigital@2020', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'super_admin']);
        echo "Default super admin user created.\n";
    } else {
        echo "Super admin user already exists.\n";
    }
    
    echo "\nInstallation completed successfully!\n";
    echo "Default login credentials:\n";
    echo "Username: admin\n";
    echo "Password: Agondigital@2020\n";
    
} catch (PDOException $e) {
    die("Database installation failed: " . $e->getMessage());
}
?>