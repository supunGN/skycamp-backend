<?php
require_once '../classes/Review.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? null;
$id = intval($_GET['id'] ?? 0);

$review = new Review();

if ($type === 'destination') {
    echo json_encode($review->getDestinationReviews($id));
} elseif ($type === 'service_provider') {
    echo json_encode($review->getServiceProviderReviews($id));
} else {
    echo json_encode([]);
}
