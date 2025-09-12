<?php


/**
 * SkyCamp Backend - Front Controller
 * Routes all API requests to appropriate controllers
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Start output buffering
ob_start();

// Include autoloader (simple require_once for each class)
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../app/Core/Validator.php';
require_once __DIR__ . '/../app/Core/Uploader.php';
require_once __DIR__ . '/../app/Core/Password.php';
require_once __DIR__ . '/../app/Core/Uuid.php';

// Include models
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/Renter.php';
require_once __DIR__ . '/../app/Models/Guide.php';

// Include repositories
require_once __DIR__ . '/../app/Repositories/UserRepository.php';
require_once __DIR__ . '/../app/Repositories/CustomerRepository.php';
require_once __DIR__ . '/../app/Repositories/RenterRepository.php';
require_once __DIR__ . '/../app/Repositories/GuideRepository.php';

// Include services
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/FileService.php';

// Include controllers
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LocationController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

// Include middlewares
require_once __DIR__ . '/../app/Middlewares/Cors.php';

try {
    // Initialize CORS middleware
    $cors = new Cors();
    $cors->handle();

    // Initialize router
    $router = new Router();

    // Initialize request and response
    $request = new Request();
    $response = new Response();

    // Define routes
    $router->get('/api/auth/me', [AuthController::class, 'me']);
    $router->post('/api/auth/register', [AuthController::class, 'register']);
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->post('/api/auth/logout', [AuthController::class, 'logout']);

    // Admin routes
    $router->post('/api/admin/login', [AdminController::class, 'login']);
    $router->get('/api/admin/me', [AdminController::class, 'me']);
    $router->post('/api/admin/logout', [AdminController::class, 'logout']);

    // Location proxy endpoints
    $router->get('/api/location/search', [LocationController::class, 'search']);
    $router->get('/api/location/reverse', [LocationController::class, 'reverse']);

    // Handle OPTIONS requests for CORS preflight
    $router->options('/api/*', function (Request $request, Response $response) {
        // CORS middleware already handled this, just return 204
        $response->setStatusCode(204);
        $response->send();
    });

    // Handle the request
    $router->dispatch($request, $response);
} catch (Exception $e) {
    // Log error
    error_log("SkyCamp Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}

// Clean output buffer
ob_end_flush();
