<?php

/**
 * SkyCamp Backend API Entry Point
 * Simple MVC Architecture Implementation
 * Handles all API requests and routes them to appropriate controllers
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for API responses
header('Content-Type: application/json');

// Enable CORS for frontend integration
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/utils/ErrorHandler.php';

// Initialize error handler
ErrorHandler::init();

// Initialize configuration
Config::load();

try {
    // Create router instance
    $router = new Router();

    // Define API routes

    // Authentication routes
    $router->post('/api/auth/login', 'LoginController@login');
    $router->post('/api/auth/logout', 'LoginController@logout');

    // Registration routes
    $router->post('/api/auth/register/customer', 'CustomerRegistrationController@register');
    $router->post('/api/auth/register/guide', 'GuideRegistrationController@register');
    $router->post('/api/auth/register/renter', 'RenterRegistrationController@register');

    // Password reset routes
    $router->post('/api/auth/password/request-reset', 'OTPPasswordResetController@requestReset');
    $router->post('/api/auth/password/verify-otp', 'OTPPasswordResetController@verifyOTP');
    $router->post('/api/auth/password/reset', 'OTPPasswordResetController@resetPassword');

    // Health check route
    $router->get('/api/health', function () {
        echo json_encode([
            'success' => true,
            'message' => 'SkyCamp API is running',
            'version' => '1.0.0',
            'timestamp' => date('c')
        ]);
    });

    // Test route for debugging
    $router->get('/api/test', function () {
        echo json_encode([
            'success' => true,
            'message' => 'API test successful',
            'server_info' => [
                'php_version' => PHP_VERSION,
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'request_uri' => $_SERVER['REQUEST_URI'],
                'timestamp' => date('c')
            ]
        ]);
    });

    // Dispatch the request
    $router->dispatch();
} catch (Exception $e) {
    // Handle any uncaught exceptions
    ErrorHandler::log($e);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => Config::isDebug() ? $e->getMessage() : 'An unexpected error occurred'
    ]);
}
