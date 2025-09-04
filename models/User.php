<?php

/**
 * User Model Class
 * Handles all User table database operations
 * Following OOP principles and MVC architecture
 */

require_once __DIR__ . '/../config/Database.php';

class User
{
    // Database connection
    private $conn;
    private $table_name = "users";

    // User properties - Encapsulation
    private $user_id;
    private $email;
    private $password_hash;
    private $role;
    private $is_active;
    private $created_at;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Setters - Following encapsulation principle
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setEmail($email)
    {
        $this->email = filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    public function setPasswordHash($password)
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setRole($role)
    {
        $this->role = htmlspecialchars(strip_tags($role));
    }

    public function setIsActive($is_active)
    {
        $this->is_active = (bool)$is_active;
    }

    /**
     * Getters - Following encapsulation principle
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Create new user in database
     * Uses prepared statements to prevent SQL injection
     * 
     * @return string|false User ID on success, false on failure
     */
    public function create()
    {
        // Generate UUID for user_id
        $this->user_id = $this->generateUUID();

        // SQL query with placeholders
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, email, password_hash, role, is_active, created_at) 
                  VALUES (:user_id, :email, :password_hash, :role, :is_active, NOW())";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind values - Security measure
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':is_active', $this->is_active, PDO::PARAM_BOOL);

        // Execute query
        if ($stmt->execute()) {
            return $this->user_id;
        }

        // Log error for debugging
        error_log("User creation failed: " . implode(", ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Check if email already exists
     * 
     * @param string $email Email to check
     * @return bool True if exists, false otherwise
     */
    public function emailExists($email)
    {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|false User data on success, false on failure
     */
    public function getUserByEmail($email)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Verify user password
     * 
     * @param string $password Plain text password
     * @param string $hash Stored password hash
     * @return bool True if password matches, false otherwise
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate UUID for user_id
     * 
     * @return string UUID
     */
    private function generateUUID()
    {
        // Simple UUID v4 generation
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Update user status
     * 
     * @param string $user_id User ID
     * @param bool $is_active Active status
     * @return bool Success status
     */
    public function updateStatus($user_id, $is_active)
    {
        $query = "UPDATE " . $this->table_name . " SET is_active = :is_active WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':is_active', $is_active, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Get user by ID
     * 
     * @param string $user_id User ID
     * @return array|false User data on success, false on failure
     */
    public function getUserById($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Update last login time
     * 
     * @param string $user_id User ID
     * @return bool Success status
     */
    public function updateLastLogin($user_id)
    {
        try {
            // Since users table doesn't have last_login column, we'll skip this for now
            // This is optional functionality
            return true;
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
}
