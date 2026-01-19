<?php
/**
 * Database Connection Class
 * Handles MySQL database connection using PDO
 */
class Database {
    // Database credentials
    private $host = "localhost";
    private $db_name = "fund_management";
    private $username = "root";
    private $password = "";
    public $conn;

    /**
     * Establish database connection
     * Returns PDO connection object
     */
    public function getConnection() {
        $this->conn = null;
        try {
            // Create PDO connection with UTF-8 charset
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on error
                    PDO::ATTR_EMULATE_PREPARES => false,           // Use real prepared statements
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  // Return associative arrays
                ]
            );
            
            // Fix MySQL GROUP BY issue
            $this->conn->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            
        } catch(PDOException $exception) {
            // Log error and show user-friendly message
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}
?>