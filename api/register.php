<?php

/**
 * User Registration API Endpoint
 */
require_once '../controllers/AuthController.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --------------------------------------
// Handle CORS
// --------------------------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --------------------------------------
// Only allow POST requests
// --------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // Debugging logs for POST and FILES
    file_put_contents(__DIR__ . '/debug_register.log', print_r($_POST, true) . print_r($_FILES, true), FILE_APPEND);

    $authController = new AuthController();
    $authController->register();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected server error: ' . $e->getMessage()
    ]);
    exit;
}
