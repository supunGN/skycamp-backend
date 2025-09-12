<?php

/**
 * Renter Repository
 * Handles all database operations for renters (matches existing renters table)
 */

class RenterRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new renter
     */
    public function create(array $data): void
    {
        $sql = "INSERT INTO renters (
            renter_id, user_id, first_name, last_name, dob, phone_number,
            home_address, gender, profile_picture, nic_number, nic_front_image, nic_back_image,
            camping_destinations, stargazing_spots, district, verification_status,
            latitude, longitude, created_at
        ) VALUES (
            :renter_id, :user_id, :first_name, :last_name, :dob, :phone_number,
            :home_address, :gender, :profile_picture, :nic_number, :nic_front_image, :nic_back_image,
            :camping_destinations, :stargazing_spots, :district, :verification_status,
            :latitude, :longitude, :created_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Find renter by ID
     */
    public function findById(string $renterId): ?Renter
    {
        $sql = "SELECT * FROM renters WHERE renter_id = :renter_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['renter_id' => $renterId]);

        $data = $stmt->fetch();
        return $data ? new Renter($data) : null;
    }

    /**
     * Find renter by user ID
     */
    public function findByUserId(string $userId): ?Renter
    {
        $sql = "SELECT * FROM renters WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $data = $stmt->fetch();
        return $data ? new Renter($data) : null;
    }

    /**
     * Update renter
     */
    public function update(array $data): bool
    {
        $sql = "UPDATE renters SET 
            first_name = :first_name, last_name = :last_name, dob = :dob,
            phone_number = :phone_number, home_address = :home_address,
            gender = :gender, profile_picture = :profile_picture,
            nic_number = :nic_number, nic_front_image = :nic_front_image, nic_back_image = :nic_back_image,
            camping_destinations = :camping_destinations,
            stargazing_spots = :stargazing_spots, district = :district,
            verification_status = :verification_status,
            latitude = :latitude, longitude = :longitude
            WHERE renter_id = :renter_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete renter
     */
    public function delete(string $renterId): bool
    {
        $sql = "DELETE FROM renters WHERE renter_id = :renter_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['renter_id' => $renterId]);
    }

    /**
     * Check if NIC number exists
     */
    public function existsByNic(string $nicNumber, ?string $excludeRenterId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM renters WHERE nic_number = :nic_number";
        $params = ['nic_number' => $nicNumber];

        if ($excludeRenterId) {
            $sql .= " AND renter_id != :exclude_renter_id";
            $params['exclude_renter_id'] = $excludeRenterId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
