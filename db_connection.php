<?php
// db_connection.php - Database connection utility

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
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
        $port = $env['DB_PORT'] ?? '3306';
        $dbname = $env['DB_DATABASE'] ?? 'webnetics-shorturl-shorturl';
        $username = $env['DB_USERNAME'] ?? 'root';
        $password = $env['DB_PASSWORD'] ?? '';
        
        try {
            $this->connection = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>