<?php

/**
 * Verify OTP API Endpoint
 * Handles OTP verification for password reset
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
    $token = $input['token'] ?? $_POST['token'] ?? '';
    $otp = $input['otp'] ?? $_POST['otp'] ?? '';

    if (empty($token) || empty($otp)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Token and OTP are required',
            'errors' => [
                'token' => empty($token) ? 'Token is required' : null,
                'otp' => empty($otp) ? 'OTP is required' : null
            ]
        ]);
        exit();
    }

    // Create controller instance and verify OTP
    $resetController = new OTPPasswordResetController();
    $result = $resetController->verifyOTP($token, $otp);

    // Set appropriate HTTP status code
    $statusCode = $result['success'] ? 200 : 400;
    http_response_code($statusCode);

    // Return JSON response
    echo json_encode($result);
} catch (Exception $e) {
    // Handle errors
    error_log("Verify OTP API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'errors' => ['system' => 'An unexpected error occurred']
    ]);
}
