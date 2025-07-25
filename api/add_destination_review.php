<?php
require_once '../classes/Review.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['destinationId'], $data['userId'], $data['rating'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$review = new Review();
$success = $review->addDestinationReview(
    $data['destinationId'],
    $data['userId'],
    $data['rating'],
    $data['reviewText'] ?? ''
);

echo json_encode(["success" => $success]);
