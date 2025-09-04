<?php

/**
 * Check Session API Endpoint
 * Validates if user session is active and returns user data
 */

// Set headers for JSON response and CORS - must be set before any output
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once __DIR__ . '/../../utils/SessionManager.php';

try {
    // Create session manager instance
    $sessionManager = new SessionManager();

    // Check if session is valid
    if ($sessionManager->isValidSession()) {
        // Get current user data
        $userData = $sessionManager->getCurrentUser();

        if ($userData) {
            // Return success with user data
            echo json_encode([
                'success' => true,
                'message' => 'Session is valid',
                'data' => [
                    'user_id' => $userData['user_id'],
                    'email' => $userData['email'],
                    'user_role' => $userData['user_role'],
                    'full_name' => $userData['full_name'],
                    'login_time' => $userData['login_time'],
                    'last_activity' => $userData['last_activity']
                ],
                'user' => $userData
            ]);
        } else {
            // Session exists but no user data
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid session data',
                'data' => null
            ]);
        }
    } else {
        // Session is invalid or expired
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session is invalid or expired',
            'data' => null
        ]);
    }
} catch (Exception $e) {
    // Handle errors
    error_log("Check session API error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'data' => null
    ]);
}
