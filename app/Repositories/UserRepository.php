<?php

/**
 * User Repository
 * Handles all database operations for users (matches existing users table)
 */

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new user
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO users (
            user_id, email, password_hash, role, is_active, created_at
        ) VALUES (
            :user_id, :email, :password_hash, :role, :is_active, :created_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $data['user_id'];
    }

    /**
     * Find user by ID
     */
    public function findById(string $userId): ?User
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $data = $stmt->fetch();
        return $data ? new User($data) : null;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $data = $stmt->fetch();
        return $data ? new User($data) : null;
    }

    /**
     * Check if email exists
     */
    public function existsByEmail(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Update user
     */
    public function update(array $data): bool
    {
        $sql = "UPDATE users SET 
            email = :email, role = :role, is_active = :is_active
            WHERE user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Update user password
     */
    public function updatePassword(string $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'user_id' => $userId,
            'password_hash' => $hashedPassword
        ]);
    }

    /**
     * Delete user
     */
    public function delete(string $userId): bool
    {
        $sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['user_id' => $userId]);
    }
}
