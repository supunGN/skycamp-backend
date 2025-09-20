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
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Wishlist.php';
require_once __DIR__ . '/../app/Models/WishlistItem.php';

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
require_once __DIR__ . '/../app/Repositories/NotificationRepository.php';
require_once __DIR__ . '/../app/Repositories/WishlistRepository.php';

// Include services
require_once __DIR__ . '/../app/Services/FileService.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/NotificationService.php';

// Include controllers
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/LocationController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

require_once __DIR__ . '/../app/Controllers/RenterController.php';
require_once __DIR__ . '/../app/Controllers/GuideController.php';
require_once __DIR__ . '/../app/Controllers/EquipmentController.php';

require_once __DIR__ . '/../app/Controllers/TravelBuddyController.php';
require_once __DIR__ . '/../app/Controllers/TempAdminController.php';
require_once __DIR__ . '/../app/Controllers/NotificationController.php';
require_once __DIR__ . '/../app/Controllers/WishlistController.php';

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
    $router->get('/api/auth/verification/docs', [AuthController::class, 'getVerificationDocs']);

    // Admin routes
    $router->post('/api/admin/login', [AdminController::class, 'login']);
    $router->get('/api/admin/me', [AdminController::class, 'me']);
    $router->post('/api/admin/logout', [AdminController::class, 'logout']);

    // Temporary admin routes for development
    $router->post('/api/temp-admin/verify-customer', [TempAdminController::class, 'verifyCustomer']);
    $router->get('/api/temp-admin/pending-verifications', [TempAdminController::class, 'getPendingVerifications']);

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


    // Renter endpoints
    $router->get('/api/renters', [RenterController::class, 'list']);
    $router->get('/api/renters/by-district', [RenterController::class, 'getByDistrict']);
    $router->get('/api/renters/:id', [RenterController::class, 'show']);

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
        $pathOnly = parse_url($requestUri, PHP_URL_PATH);
        $relativeFilePath = str_replace('/api/files/', '', $pathOnly);

        // Resolve potential locations: public mirror first, then storage path
        $publicPath = __DIR__ . '/storage/uploads/' . ltrim($relativeFilePath, '/\\');
        $storagePath = rtrim($fileService->getStoragePath(), '/\\') . '/' . ltrim($relativeFilePath, '/\\');

        if (file_exists($publicPath)) {
            $fileService->serveFile($publicPath);
            return;
        }

        $fileService->serveFile($storagePath);
    });

    // Guide endpoints
    $router->get('/api/guides', [GuideController::class, 'list']);
    $router->get('/api/guides/by-district', [GuideController::class, 'getByDistrict']);
    $router->get('/api/guides/:id', [GuideController::class, 'show']);

    // Equipment endpoints
    $router->get('/api/equipment/categories', [EquipmentController::class, 'getCategories']);
    $router->get('/api/equipment/categories-with-equipment', [EquipmentController::class, 'getCategoriesWithEquipment']);
    $router->get('/api/equipment/by-category', [EquipmentController::class, 'getEquipmentByCategory']);

    // Admin User Management endpoints
    $router->get('/api/admin/users/customers', [AdminController::class, 'getCustomers']);
    $router->get('/api/admin/users/renters', [AdminController::class, 'getRenters']);
    $router->get('/api/admin/users/guides', [AdminController::class, 'getGuides']);
    $router->get('/api/admin/users/suspended', [AdminController::class, 'getSuspendedUsers']);
    $router->get('/api/admin/users/deleted', [AdminController::class, 'getDeletedUsers']);
    $router->post('/api/admin/users/suspend', [AdminController::class, 'suspendUser']);
    $router->post('/api/admin/users/activate', [AdminController::class, 'activateUser']);
    $router->post('/api/admin/users/delete', [AdminController::class, 'deleteUser']);
    $router->get('/api/admin/activity-log', [AdminController::class, 'getActivityLog']);

    // Admin User Verification endpoints
    $router->get('/api/admin/verifications/pending', [AdminController::class, 'getPendingVerifications']);
    $router->get('/api/admin/verifications/pending-count', [AdminController::class, 'getPendingVerificationCount']);
    $router->get('/api/admin/verifications/rejected', [AdminController::class, 'getRejectedUsers']);
    $router->post('/api/admin/verifications/approve', [AdminController::class, 'approveUser']);
    $router->post('/api/admin/verifications/reject', [AdminController::class, 'rejectUser']);
    $router->get('/api/admin/verifications/activity-log', [AdminController::class, 'getVerificationActivityLog']);

    // Notification endpoints
    $router->get('/api/notifications', [NotificationController::class, 'getUserNotifications']);
    $router->get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    $router->post('/api/notifications/mark-read', [NotificationController::class, 'markAsRead']);
    $router->post('/api/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Wishlist endpoints
    $router->get('/api/wishlist', [WishlistController::class, 'getWishlist']);
    $router->post('/api/wishlist/add', [WishlistController::class, 'addItem']);
    $router->post('/api/wishlist/remove', [WishlistController::class, 'removeItem']);
    $router->get('/api/wishlist/check', [WishlistController::class, 'checkItem']);
    $router->get('/api/wishlist/count', [WishlistController::class, 'getItemCount']);
    $router->post('/api/wishlist/clear', [WishlistController::class, 'clearWishlist']);


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
