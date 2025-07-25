<?php
/**
 * Forgot Password API Endpoint
 */
require_once '../controllers/AuthController.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Handle forgot password
$authController = new AuthController();
$authController->forgotPassword();
?>
