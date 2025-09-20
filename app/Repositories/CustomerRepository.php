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
    public function create(array $data): string
    {
        $sql = "INSERT INTO customers (
            user_id, first_name, last_name, dob, phone_number,
            home_address, location, latitude, longitude, gender, profile_picture, 
            nic_number, nic_front_image, nic_back_image, travel_buddy_status, 
            verification_status, created_at
        ) VALUES (
            :user_id, :first_name, :last_name, :dob, :phone_number,
            :home_address, :location, :latitude, :longitude, :gender, :profile_picture, 
            :nic_number, :nic_front_image, :nic_back_image, :travel_buddy_status, 
            :verification_status, :created_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'dob' => $data['dob'],
            'phone_number' => $data['phone_number'],
            'home_address' => $data['home_address'],
            'location' => $data['location'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'gender' => $data['gender'],
            'profile_picture' => $data['profile_picture'],
            'nic_number' => $data['nic_number'],
            'nic_front_image' => $data['nic_front_image'],
            'nic_back_image' => $data['nic_back_image'],
            'travel_buddy_status' => $data['travel_buddy_status'],
            'verification_status' => $data['verification_status'],
            'created_at' => $data['created_at']
        ]);

        return $this->pdo->lastInsertId();
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

    /**
     * Update travel buddy status for a customer
     */
    public function updateTravelBuddyStatus(string $customerId, string $status): bool
    {
        if (!in_array($status, ['Active', 'Inactive'])) {
            throw new InvalidArgumentException('Invalid travel buddy status. Must be Active or Inactive.');
        }

        $sql = "UPDATE customers SET travel_buddy_status = :status WHERE customer_id = :customer_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'customer_id' => $customerId,
            'status' => $status
        ]);
    }

    /**
     * Update travel buddy status by user ID
     */
    public function updateTravelBuddyStatusByUserId(string $userId, string $status): bool
    {
        if (!in_array($status, ['Active', 'Inactive'])) {
            throw new InvalidArgumentException('Invalid travel buddy status. Must be Active or Inactive.');
        }

        $sql = "UPDATE customers SET travel_buddy_status = :status WHERE user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'status' => $status
        ]);
    }

    /**
     * Update verification documents for a customer
     */
    public function updateVerificationDocuments(string $userId, string $nicFrontImage, string $nicBackImage): bool
    {
        $sql = "UPDATE customers SET 
            nic_front_image = :nic_front_image, 
            nic_back_image = :nic_back_image,
            verification_status = 'Pending'
            WHERE user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'nic_front_image' => $nicFrontImage,
            'nic_back_image' => $nicBackImage
        ]);
    }

    /**
     * Update verification status for a customer
     */
    public function updateVerificationStatus(string $userId, string $status): bool
    {
        $sql = "UPDATE customers SET verification_status = :status WHERE user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'status' => $status
        ]);
    }

    /**
     * Get customers by verification status
     */
    public function getCustomersByVerificationStatus(string $status): array
    {
        $sql = "SELECT c.*, u.email, u.first_name, u.last_name 
                FROM customers c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.verification_status = :status";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['status' => $status]);
        
        return $stmt->fetchAll();
    }
}
