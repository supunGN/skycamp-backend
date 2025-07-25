<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once '../config/database.php';
require_once '../classes/Customer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$userId = $data['id'];

try {
    $db = Database::getInstance()->getConnection();
    // Only allow updating certain fields
    $fields = [
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'profile_picture'
    ];
    $set = [];
    $params = [];
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $set[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    if (empty($set)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update.']);
        exit;
    }
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(", ", $set) . ", updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Fetch updated user
    $user = Customer::getById($userId);
    echo json_encode(['success' => true, 'user' => $user]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
