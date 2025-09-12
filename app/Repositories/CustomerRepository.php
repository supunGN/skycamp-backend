<?php

/**
 * Customer Repository
 * Handles all database operations for customers (matches existing customers table)
 */

class CustomerRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new customer
     */
    public function create(array $data): void
    {
        $sql = "INSERT INTO customers (
            customer_id, user_id, first_name, last_name, dob, phone_number,
            home_address, location, latitude, longitude, gender, profile_picture, 
            nic_number, nic_front_image, nic_back_image, travel_buddy_status, 
            verification_status, created_at
        ) VALUES (
            :customer_id, :user_id, :first_name, :last_name, :dob, :phone_number,
            :home_address, :location, :latitude, :longitude, :gender, :profile_picture, 
            :nic_number, :nic_front_image, :nic_back_image, :travel_buddy_status, 
            :verification_status, :created_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Find customer by ID
     */
    public function findById(string $customerId): ?Customer
    {
        $sql = "SELECT * FROM customers WHERE customer_id = :customer_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);

        $data = $stmt->fetch();
        return $data ? new Customer($data) : null;
    }

    /**
     * Find customer by user ID
     */
    public function findByUserId(string $userId): ?Customer
    {
        $sql = "SELECT * FROM customers WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $data = $stmt->fetch();
        return $data ? new Customer($data) : null;
    }

    /**
     * Update customer
     */
    public function update(array $data): bool
    {
        $sql = "UPDATE customers SET 
            first_name = :first_name, last_name = :last_name, dob = :dob,
            phone_number = :phone_number, home_address = :home_address,
            location = :location, latitude = :latitude, longitude = :longitude,
            gender = :gender, profile_picture = :profile_picture,
            nic_number = :nic_number, nic_front_image = :nic_front_image, 
            nic_back_image = :nic_back_image, travel_buddy_status = :travel_buddy_status,
            verification_status = :verification_status
            WHERE customer_id = :customer_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete customer
     */
    public function delete(string $customerId): bool
    {
        $sql = "DELETE FROM customers WHERE customer_id = :customer_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['customer_id' => $customerId]);
    }

    /**
     * Check if NIC number exists
     */
    public function existsByNic(string $nicNumber, ?string $excludeCustomerId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM customers WHERE nic_number = :nic_number";
        $params = ['nic_number' => $nicNumber];

        if ($excludeCustomerId) {
            $sql .= " AND customer_id != :exclude_customer_id";
            $params['exclude_customer_id'] = $excludeCustomerId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
