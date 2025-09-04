<?php

/**
 * Forgot Password API Endpoint
 * Handles password reset request and OTP generation
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

// Include required files
require_once __DIR__ . '/../../../controllers/OTPPasswordResetController.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? $_POST['email'] ?? '';

    if (empty($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email is required',
            'errors' => ['email' => 'Please provide an email address']
        ]);
        exit();
    }

    // Create controller instance and handle password reset request
    $resetController = new OTPPasswordResetController();
    $result = $resetController->requestReset($email);

    // Set appropriate HTTP status code
    $statusCode = $result['success'] ? 200 : 400;
    http_response_code($statusCode);

    // Return JSON response
    echo json_encode($result);
} catch (Exception $e) {
    // Handle errors
    error_log("Forgot password API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'errors' => ['system' => 'An unexpected error occurred']
    ]);
}
