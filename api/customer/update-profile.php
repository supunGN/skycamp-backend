<?php

/**
 * Update User Profile API Endpoint
 * Handles updating profile information for all user types
 */

// Set headers for JSON response and CORS - must be set before any output
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST and PUT requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Include required files
require_once __DIR__ . '/../../utils/SessionManager.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Customer.php';
require_once __DIR__ . '/../../models/Guide.php';
require_once __DIR__ . '/../../models/Renter.php';
require_once __DIR__ . '/../../utils/ValidationService.php';

try {
    // Check session
    $sessionManager = new SessionManager();

    if (!$sessionManager->isValidSession()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'data' => null
        ]);
        exit();
    }

    // Get current user data
    $userData = $sessionManager->getCurrentUser();

    if (!$userData) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user session',
            'data' => null
        ]);
        exit();
    }

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $data = $input ?: $_POST;

    // Determine user type for validation
    $userType = 'Customer'; // Default
    if ($userData['user_role'] === 'service_provider') {
        if (isset($userData['provider_type'])) {
            if ($userData['provider_type'] === 'Local Guide') {
                $userType = 'Guide';
            } elseif ($userData['provider_type'] === 'Equipment Renter') {
                $userType = 'Renter';
            }
        }
    }

    // Validate input data
    $validator = new ValidationService();
    $validation = $validator->validateProfileData($data, $userType);

    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation['errors']
        ]);
        exit();
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    $updateResult = false;
    $updatedData = null;

    // Update profile based on user role
    switch ($userData['user_role']) {
        case 'customer':
            $customer = new Customer($db);
            $updateResult = $customer->updateProfile($userData['user_id'], $data);
            if ($updateResult) {
                $updatedData = $customer->getCustomerByUserId($userData['user_id']);
            }
            break;

        case 'service_provider':
            if (isset($userData['provider_type'])) {
                if ($userData['provider_type'] === 'Local Guide') {
                    $guide = new Guide($db);
                    $updateResult = $guide->updateProfile($userData['user_id'], $data);
                    if ($updateResult) {
                        $updatedData = $guide->getByUserId($userData['user_id']);
                    }
                } elseif ($userData['provider_type'] === 'Equipment Renter') {
                    $renter = new Renter($db);
                    $updateResult = $renter->updateProfile($userData['user_id'], $data);
                    if ($updateResult) {
                        $updatedData = $renter->getByUserId($userData['user_id']);
                    }
                }
            }
            break;

        default:
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user role',
                'data' => null
            ]);
            exit();
    }

    if ($updateResult) {
        // Remove sensitive data
        if ($updatedData) {
            unset($updatedData['password_hash']);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $updatedData
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update profile',
            'data' => null
        ]);
    }
} catch (Exception $e) {
    // Handle errors
    error_log("Update profile API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'data' => null
    ]);
}
