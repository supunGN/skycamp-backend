<?php
require_once __DIR__ . '/../config/database.php';

class Review
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Add a destination review
    public function addDestinationReview($destinationId, $userId, $rating, $reviewText)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO destination_reviews (destination_id, user_id, rating, review_text)
            VALUES (:destination_id, :user_id, :rating, :review_text)
        ");
        $stmt->bindParam(":destination_id", $destinationId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":rating", $rating);
        $stmt->bindParam(":review_text", $reviewText);
        if ($stmt->execute()) {
            $this->updateDestinationRating($destinationId);
            return true;
        }
        return false;
    }

    // Update destination rating
    private function updateDestinationRating($destinationId)
    {
        $stmt = $this->conn->prepare("
            UPDATE destinations
            SET average_rating = (SELECT AVG(rating) FROM destination_reviews WHERE destination_id = :destination_id),
                total_reviews = (SELECT COUNT(*) FROM destination_reviews WHERE destination_id = :destination_id)
            WHERE id = :destination_id
        ");
        $stmt->bindParam(":destination_id", $destinationId);
        $stmt->execute();
    }

    // Add a service provider review
    public function addServiceProviderReview($serviceProviderId, $userId, $rating, $reviewText)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO service_provider_reviews (service_provider_id, user_id, rating, review_text)
            VALUES (:service_provider_id, :user_id, :rating, :review_text)
        ");
        $stmt->bindParam(":service_provider_id", $serviceProviderId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":rating", $rating);
        $stmt->bindParam(":review_text", $reviewText);
        if ($stmt->execute()) {
            $this->updateServiceProviderRating($serviceProviderId);
            return true;
        }
        return false;
    }

    // Update service provider rating
    private function updateServiceProviderRating($serviceProviderId)
    {
        $stmt = $this->conn->prepare("
            UPDATE service_providers
            SET rating = (SELECT AVG(rating) FROM service_provider_reviews WHERE service_provider_id = :service_provider_id),
                total_reviews = (SELECT COUNT(*) FROM service_provider_reviews WHERE service_provider_id = :service_provider_id)
            WHERE id = :service_provider_id
        ");
        $stmt->bindParam(":service_provider_id", $serviceProviderId);
        $stmt->execute();
    }

    // Get reviews for a destination
    public function getDestinationReviews($destinationId)
    {
        $stmt = $this->conn->prepare("
            SELECT dr.*, u.first_name, u.last_name
            FROM destination_reviews dr
            JOIN users u ON dr.user_id = u.id
            WHERE dr.destination_id = :destination_id
            ORDER BY dr.created_at DESC
        ");
        $stmt->bindParam(":destination_id", $destinationId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get reviews for a service provider
    public function getServiceProviderReviews($serviceProviderId)
    {
        $stmt = $this->conn->prepare("
            SELECT sr.*, u.first_name, u.last_name
            FROM service_provider_reviews sr
            JOIN users u ON sr.user_id = u.id
            WHERE sr.service_provider_id = :service_provider_id
            ORDER BY sr.created_at DESC
        ");
        $stmt->bindParam(":service_provider_id", $serviceProviderId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
