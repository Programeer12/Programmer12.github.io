<?php
// Database connection using PDO with better error handling
$host = 'localhost';
$db   = 'eduassign_main'; // Database name
$user = 'root';      // Change to your DB username
$pass = '';          // Change to your DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set timezone to ensure consistency between PHP and MySQL
    date_default_timezone_set('Asia/Kolkata'); // Adjust to your timezone
    $pdo->exec("SET time_zone = '+05:30'"); // Indian Standard Time (IST)
    
    // Test the connection
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly error message
    die("Database connection failed. Please check your database configuration or contact the administrator.");
}

// Database class for additional functionality
class Database {
    private $host = 'localhost';
    private $db_name = 'eduassign';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                    $this->username, 
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $exception) {
                error_log("Database connection error: " . $exception->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return $this->conn;
    }
}
?>
