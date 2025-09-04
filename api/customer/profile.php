<?php

/**
 * User Profile API Endpoint
 * Handles retrieving user profile data for all user types
 */

// Set headers for JSON response and CORS - must be set before any output
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    $profileData = null;

    // Get profile based on user role
    switch ($userData['user_role']) {
        case 'customer':
            $customer = new Customer($db);
            $profileData = $customer->getCustomerByUserId($userData['user_id']);
            break;

        case 'service_provider':
            // Check provider type for service providers
            if (isset($userData['provider_type'])) {
                if ($userData['provider_type'] === 'Local Guide') {
                    $guide = new Guide($db);
                    $profileData = $guide->getByUserId($userData['user_id']);
                } elseif ($userData['provider_type'] === 'Equipment Renter') {
                    $renter = new Renter($db);
                    $profileData = $renter->getByUserId($userData['user_id']);
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

    if ($profileData) {
        // Remove sensitive data
        unset($profileData['password_hash']);

        echo json_encode([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $profileData
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Profile not found',
            'data' => null
        ]);
    }
} catch (Exception $e) {
    // Handle errors
    error_log("Profile API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'data' => null
    ]);
}
