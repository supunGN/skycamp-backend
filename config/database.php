<?php

/**
 * Database Configuration and Connection Class
 * Implements PDO for secure database operations
 * Following OOP principles from Lecture 3 and enhanced with configuration management
 */

require_once __DIR__ . '/Config.php';

class Database
{
    // Database configuration - Encapsulation principle
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private $options;

    /**
     * Constructor - Load configuration
     */
    public function __construct()
    {
        $dbConfig = Config::getDatabaseConfig();
        $this->host = $dbConfig['host'];
        $this->db_name = $dbConfig['name'];
        $this->username = $dbConfig['username'];
        $this->password = $dbConfig['password'];
        $this->options = $dbConfig['options'];
    }

    /**
     * Get database connection using PDO
     * Implements proper error handling and security
     * 
     * @return PDO Database connection object
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            // PDO connection with security options from configuration
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection()
    {
        $this->conn = null;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->conn->rollBack();
    }
}
