<?php

/**
 * Registration API Endpoint
 * Handles user registration for all roles
 */

// Set headers for JSON response and CORS - must be set before any output
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Include required controllers
require_once __DIR__ . '/controllers/CustomerRegistrationController.php';
require_once __DIR__ . '/controllers/GuideRegistrationController.php';
require_once __DIR__ . '/controllers/RenterRegistrationController.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Get form data (support both JSON and form data)
    $data = $input ?: $_POST;
    $files = $_FILES;

    // Get the role from the data (support both 'role' and 'userRole' for frontend compatibility)
    $role = $data['role'] ?? $data['userRole'] ?? '';

    if (empty($role)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Role is required',
            'errors' => ['role' => 'Please specify a role']
        ]);
        exit();
    }

    // Route to appropriate controller based on role
    $result = null;

    switch (strtolower($role)) {
        case 'customer':
            $controller = new CustomerRegistrationController();
            $result = $controller->register($data, $files);
            break;

        case 'guide':
            $controller = new GuideRegistrationController();
            $result = $controller->register($data, $files);
            break;

        case 'renter':
            $controller = new RenterRegistrationController();
            $result = $controller->register($data, $files);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid role specified',
                'errors' => ['role' => 'Role must be customer, guide, or renter']
            ]);
            exit();
    }

    // Set appropriate HTTP status code
    $statusCode = $result['success'] ? 201 : 400;
    http_response_code($statusCode);

    // Return JSON response
    echo json_encode($result);
} catch (Exception $e) {
    // Handle errors
    error_log("Registration API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'errors' => ['system' => 'An unexpected error occurred']
    ]);
}
