<?php

/**
 * User Model
 * Represents a user in the system (matches existing users table)
 */

class User
{
    public string $userId;
    public string $email;
    public string $passwordHash;
    public string $role;
    public bool $isActive;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->userId = $data['user_id'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->passwordHash = $data['password_hash'] ?? '';
        $this->role = $data['role'] ?? 'Customer';
        $this->isActive = (bool) ($data['is_active'] ?? true);
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'password_hash' => $this->passwordHash,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Convert to array without sensitive data
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->userId,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->role === 'Customer';
    }

    /**
     * Check if user is renter
     */
    public function isRenter(): bool
    {
        return $this->role === 'Renter';
    }

    /**
     * Check if user is guide
     */
    public function isGuide(): bool
    {
        return $this->role === 'Guide';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    /**
     * Validate user data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!in_array($this->role, ['Customer', 'Renter', 'Guide', 'Admin'])) {
            $errors['role'] = 'Invalid role';
        }

        return $errors;
    }
}
