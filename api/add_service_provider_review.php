<?php
require_once '../classes/Review.php';
require_once '../utils/response.php'; // Assuming you have a standard response function

header('Content-Type: application/json');

try {
    // Get POST data (JSON)
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['serviceProviderId'], $data['userId'], $data['rating'])) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields (serviceProviderId, userId, rating)"
        ]);
        exit;
    }

    $serviceProviderId = intval($data['serviceProviderId']);
    $userId = intval($data['userId']);
    $rating = intval($data['rating']);
    $reviewText = isset($data['reviewText']) ? trim($data['reviewText']) : '';

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            "success" => false,
            "message" => "Rating must be between 1 and 5"
        ]);
        exit;
    }

    // Use Review class
    $review = new Review();
    $success = $review->addServiceProviderReview($serviceProviderId, $userId, $rating, $reviewText);

    if ($success) {
        echo json_encode([
            "success" => true,
            "message" => "Review added successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to add review"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
