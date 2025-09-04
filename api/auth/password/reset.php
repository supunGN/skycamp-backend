<?php

/**
 * Reset Password API Endpoint
 * Handles final password reset with verified token
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
    $newPassword = $input['new_password'] ?? $_POST['new_password'] ?? '';

    if (empty($token) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Token and new password are required',
            'errors' => [
                'token' => empty($token) ? 'Token is required' : null,
                'new_password' => empty($newPassword) ? 'New password is required' : null
            ]
        ]);
        exit();
    }

    // Create controller instance and reset password
    $resetController = new OTPPasswordResetController();
    $result = $resetController->resetPassword($token, $newPassword);

    // Set appropriate HTTP status code
    $statusCode = $result['success'] ? 200 : 400;
    http_response_code($statusCode);

    // Return JSON response
    echo json_encode($result);
} catch (Exception $e) {
    // Handle errors
    error_log("Reset password API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'errors' => ['system' => 'An unexpected error occurred']
    ]);
}
