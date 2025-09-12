<?php

/**
 * Guide Repository
 * Handles all database operations for guides (matches existing guides table)
 */

class GuideRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new guide
     */
    public function create(array $data): void
    {
        $sql = "INSERT INTO guides (
            guide_id, user_id, first_name, last_name, dob, phone_number,
            home_address, gender, profile_picture, nic_number, nic_front_image, nic_back_image,
            camping_destinations, stargazing_spots, district, description,
            special_note, currency, languages, price_per_day, verification_status,
            created_at
        ) VALUES (
            :guide_id, :user_id, :first_name, :last_name, :dob, :phone_number,
            :home_address, :gender, :profile_picture, :nic_number, :nic_front_image, :nic_back_image,
            :camping_destinations, :stargazing_spots, :district, :description,
            :special_note, :currency, :languages, :price_per_day, :verification_status,
            :created_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Find guide by ID
     */
    public function findById(string $guideId): ?Guide
    {
        $sql = "SELECT * FROM guides WHERE guide_id = :guide_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['guide_id' => $guideId]);

        $data = $stmt->fetch();
        return $data ? new Guide($data) : null;
    }

    /**
     * Find guide by user ID
     */
    public function findByUserId(string $userId): ?Guide
    {
        $sql = "SELECT * FROM guides WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $data = $stmt->fetch();
        return $data ? new Guide($data) : null;
    }

    /**
     * Update guide
     */
    public function update(array $data): bool
    {
        $sql = "UPDATE guides SET 
            first_name = :first_name, last_name = :last_name, dob = :dob,
            phone_number = :phone_number, home_address = :home_address,
            gender = :gender, profile_picture = :profile_picture,
            nic_number = :nic_number, nic_front_image = :nic_front_image, nic_back_image = :nic_back_image,
            camping_destinations = :camping_destinations,
            stargazing_spots = :stargazing_spots, district = :district,
            description = :description, special_note = :special_note,
            currency = :currency, languages = :languages,
            price_per_day = :price_per_day, verification_status = :verification_status
            WHERE guide_id = :guide_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete guide
     */
    public function delete(string $guideId): bool
    {
        $sql = "DELETE FROM guides WHERE guide_id = :guide_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['guide_id' => $guideId]);
    }

    /**
     * Check if NIC number exists
     */
    public function existsByNic(string $nicNumber, ?string $excludeGuideId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM guides WHERE nic_number = :nic_number";
        $params = ['nic_number' => $nicNumber];

        if ($excludeGuideId) {
            $sql .= " AND guide_id != :exclude_guide_id";
            $params['exclude_guide_id'] = $excludeGuideId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
