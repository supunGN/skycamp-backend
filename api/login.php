<?php
/**
 * User Login API Endpoint
 */
require_once '../controllers/AuthController.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Handle login
$authController = new AuthController();
$authController->login();
?>
