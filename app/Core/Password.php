<?php

/**
 * Password Class
 * Handles password hashing and verification
 */

class Password
{
    /**
     * Hash password using Argon2id (preferred) or Bcrypt
     */
    public static function hash(string $password): string
    {
        // Try Argon2id first (PHP 7.2+)
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3,         // 3 threads
            ]);
        }

        // Fallback to Argon2i (PHP 7.2+)
        if (defined('PASSWORD_ARGON2I')) {
            return password_hash($password, PASSWORD_ARGON2I, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3,         // 3 threads
            ]);
        }

        // Fallback to Bcrypt (PHP 5.5+)
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }

    /**
     * Verify password against hash
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing
     */
    public static function needsRehash(string $hash): bool
    {
        // Check if using Argon2id
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
        }

        // Check if using Argon2i
        if (defined('PASSWORD_ARGON2I')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2I, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
        }

        // Check if using Bcrypt
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }

    /**
     * Generate random password
     */
    public static function generate(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }

    /**
     * Validate password strength
     */
    public static function validateStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }

    /**
     * Get password info
     */
    public static function getInfo(string $hash): array
    {
        return password_get_info($hash);
    }
}
