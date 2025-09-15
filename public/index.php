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
require_once __DIR__ . '/../app/Models/TravelPlan.php';
require_once __DIR__ . '/../app/Models/TravelRequest.php';
require_once __DIR__ . '/../app/Models/TravelChat.php';
require_once __DIR__ . '/../app/Models/TravelMessage.php';

// Include repositories
require_once __DIR__ . '/../app/Repositories/UserRepository.php';
require_once __DIR__ . '/../app/Repositories/CustomerRepository.php';
require_once __DIR__ . '/../app/Repositories/RenterRepository.php';
require_once __DIR__ . '/../app/Repositories/GuideRepository.php';
require_once __DIR__ . '/../app/Repositories/LocationRepository.php';
require_once __DIR__ . '/../app/Repositories/ReminderRepository.php';
require_once __DIR__ . '/../app/Repositories/TravelPlanRepository.php';
require_once __DIR__ . '/../app/Repositories/TravelRequestRepository.php';
require_once __DIR__ . '/../app/Repositories/TravelChatRepository.php';
require_once __DIR__ . '/../app/Repositories/TravelMessageRepository.php';

// Include services
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/FileService.php';

// Include controllers
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LocationController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';
require_once __DIR__ . '/../app/Controllers/TravelBuddyController.php';

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
    $router->post('/api/auth/profile', [AuthController::class, 'updateProfile']);
    $router->post('/api/auth/travel-buddy/toggle', [AuthController::class, 'toggleTravelBuddy']);
    $router->post('/api/auth/verification/submit', [AuthController::class, 'submitVerification']);

    // Admin routes
    $router->post('/api/admin/login', [AdminController::class, 'login']);
    $router->get('/api/admin/me', [AdminController::class, 'me']);
    $router->post('/api/admin/logout', [AdminController::class, 'logout']);

    // Location proxy endpoints
    $router->get('/api/location/search', [LocationController::class, 'search']);
    $router->get('/api/location/reverse', [LocationController::class, 'reverse']);

    // Location data endpoints
    $router->get('/api/locations/camping', [LocationController::class, 'getCampingDestinations']);
    $router->get('/api/locations/stargazing', [LocationController::class, 'getStargazingSpots']);
    $router->get('/api/locations/all', [LocationController::class, 'getAllLocations']);
    $router->get('/api/locations/by-type', [LocationController::class, 'getLocationsByType']);

    // Location display endpoints with images
    $router->get('/api/locations/camping/display', [LocationController::class, 'getCampingDestinationsWithImages']);
    $router->get('/api/locations/stargazing/display', [LocationController::class, 'getStargazingSpotsWithImages']);

    // Home page endpoints (top 3 for each type)
    $router->get('/api/locations/camping/top', [LocationController::class, 'getTopCampingDestinationsWithImages']);
    $router->get('/api/locations/stargazing/top', [LocationController::class, 'getTopStargazingSpotsWithImages']);

    // District filtering endpoints (must be before :id route)
    $router->get('/api/locations/camping/by-district', [LocationController::class, 'getCampingDestinationsWithImagesByDistrict']);
    $router->get('/api/locations/stargazing/by-district', [LocationController::class, 'getStargazingSpotsWithImagesByDistrict']);
    $router->get('/api/locations/districts', [LocationController::class, 'getAllDistricts']);

    // Individual location endpoint (must be after specific routes)
    $router->get('/api/locations/:id', [LocationController::class, 'getLocationWithImages']);

    // Travel Buddy endpoints
    $router->get('/api/travel-plans', [TravelBuddyController::class, 'listPlans']);
    $router->post('/api/travel-plans', [TravelBuddyController::class, 'createPlan']);
    $router->post('/api/travel-requests', [TravelBuddyController::class, 'requestJoin']);
    $router->get('/api/travel-messages', [TravelBuddyController::class, 'listMessages']);
    $router->post('/api/travel-messages', [TravelBuddyController::class, 'sendMessage']);
    $router->get('/api/travel-buddy/status', [TravelBuddyController::class, 'getStatus']);

    // File serving endpoint for uploaded images
    $router->get('/api/files/*', function (Request $request, Response $response) {
        $fileService = new FileService();
        $requestUri = $_SERVER['REQUEST_URI'];
        $filePath = str_replace('/api/files/', '', $requestUri);

        // Security: prevent directory traversal
        $filePath = str_replace('../', '', $filePath);

        $fullPath = $fileService->getStoragePath() . '/' . $filePath;

        if (file_exists($fullPath)) {
            $fileService->serveFile($fullPath);
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'File not found']);
        }
    });

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
