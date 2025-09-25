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

// Set CORS headers for development
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Start output buffering
ob_start();

// Include autoloader (simple require_once for each class)
require_once __DIR__ . '/../app/Config/database.php';

// Initialize database connection
Database::init();
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
require_once __DIR__ . '/../app/Controllers/RenterDashboardController.php';
require_once __DIR__ . '/../app/Controllers/GuideController.php';
require_once __DIR__ . '/../app/Controllers/GuideDashboardController.php';
require_once __DIR__ . '/../app/Controllers/EquipmentController.php';

require_once __DIR__ . '/../app/Controllers/TravelBuddyController.php';
require_once __DIR__ . '/../app/Controllers/NotificationController.php';
require_once __DIR__ . '/../app/Controllers/WishlistController.php';
require_once __DIR__ . '/../app/Controllers/CartController.php';
require_once __DIR__ . '/../app/Controllers/BookingController.php';

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
    $router->get('/api/renters/by-equipment', [RenterController::class, 'getByEquipment']);
    $router->get('/api/renters/:id', [RenterController::class, 'show']);

    // Renter Dashboard endpoints
    $router->get('/api/renter/dashboard/stats', [RenterDashboardController::class, 'getDashboardStats']);
    $router->get('/api/renter/profile', [RenterDashboardController::class, 'getProfile']);
    $router->post('/api/renter/profile', [RenterDashboardController::class, 'updateProfile']);
    $router->get('/api/renter/verification/docs', [RenterDashboardController::class, 'getVerificationDocs']);
    $router->post('/api/renter/verification/submit', [RenterDashboardController::class, 'submitVerification']);

    // Renter Equipment Management endpoints
    $router->get('/api/renter/equipment/catalog', [RenterDashboardController::class, 'getEquipmentCatalog']);
    $router->get('/api/renter/equipment/list', [RenterDashboardController::class, 'getRenterEquipment']);
    $router->post('/api/renter/equipment/add', [RenterDashboardController::class, 'addEquipment']);
    $router->put('/api/renter/equipment/update/:id', [RenterDashboardController::class, 'updateEquipment']);
    $router->post('/api/renter/equipment/update/:id', [RenterDashboardController::class, 'updateEquipmentWithPhotos']);
    $router->put('/api/renter/equipment/delete/:id', [RenterDashboardController::class, 'deleteEquipment']);
    $router->put('/api/renter/equipment/restore/:id', [RenterDashboardController::class, 'restoreEquipment']);
    $router->put('/api/renter/equipment/photo/:photoId/set-primary', [RenterDashboardController::class, 'setPrimaryPhoto']);
    $router->delete('/api/renter/equipment/photo/:photoId', [RenterDashboardController::class, 'removeEquipmentPhoto']);

    // Renter Location Management endpoints
    $router->get('/api/renter/locations/available', [RenterDashboardController::class, 'getAvailableLocations']);
    $router->get('/api/renter/locations/coverage', [RenterDashboardController::class, 'getRenterLocations']);
    $router->put('/api/renter/locations/update', [RenterDashboardController::class, 'updateRenterLocations']);
    $router->get('/api/renter/locations/check-removal/:locationName', [RenterDashboardController::class, 'checkLocationRemoval']);

    // Renter Bookings endpoints
    $router->get('/api/renter/bookings', [RenterDashboardController::class, 'getRenterBookings']);
    $router->put('/api/renter/bookings/:id/mark-received', [RenterDashboardController::class, 'markBookingAsReceived']);

    // Renter Test endpoints
    $router->post('/api/renter/test-notifications', [RenterDashboardController::class, 'createTestNotifications']);

    // Travel Buddy endpoints
    $router->get('/api/travel-plans', [TravelBuddyController::class, 'listPlans']);
    $router->post('/api/travel-plans', [TravelBuddyController::class, 'createPlan']);
    $router->get('/api/travel-plans/my', [TravelBuddyController::class, 'getMyPlans']);
    $router->get('/api/travel-plans/:id', [TravelBuddyController::class, 'getPlan']);
    $router->put('/api/travel-plans/:id', [TravelBuddyController::class, 'updatePlan']);
    $router->delete('/api/travel-plans/:id', [TravelBuddyController::class, 'deletePlan']);

    $router->post('/api/travel-requests', [TravelBuddyController::class, 'requestJoin']);
    $router->get('/api/travel-requests/my', [TravelBuddyController::class, 'getMyRequests']);
    $router->post('/api/travel-requests/:id/accept', [TravelBuddyController::class, 'acceptRequest']);
    $router->post('/api/travel-requests/:id/reject', [TravelBuddyController::class, 'rejectRequest']);

    $router->get('/api/travel-messages', [TravelBuddyController::class, 'listMessages']);
    $router->post('/api/travel-messages', [TravelBuddyController::class, 'sendMessage']);
    $router->get('/api/travel-buddy/debug', [TravelBuddyController::class, 'debug']);
    $router->get('/api/travel-buddy/status', [TravelBuddyController::class, 'getStatus']);

    // Guide endpoints
    $router->get('/api/guides', [GuideController::class, 'list']);
    $router->get('/api/guides/by-district', [GuideController::class, 'getByDistrict']);
    $router->get('/api/guides/:id', [GuideController::class, 'show']);

    // Guide Dashboard endpoints
    $router->get('/api/guide/dashboard/stats', [GuideDashboardController::class, 'getDashboardStats']);
    $router->get('/api/guide/profile', [GuideDashboardController::class, 'getProfile']);
    $router->post('/api/guide/profile', [GuideDashboardController::class, 'updateProfile']);
    $router->get('/api/guide/verification/docs', [GuideDashboardController::class, 'getVerificationDocs']);
    $router->post('/api/guide/verification/submit', [GuideDashboardController::class, 'submitVerification']);
    $router->get('/api/guide/availability', [GuideDashboardController::class, 'getAvailability']);
    $router->post('/api/guide/availability', [GuideDashboardController::class, 'updateAvailability']);
    $router->get('/api/guide/images', [GuideDashboardController::class, 'getImages']);
    $router->post('/api/guide/images', [GuideDashboardController::class, 'uploadImages']);
    $router->delete('/api/guide/images/:id', [GuideDashboardController::class, 'deleteImage']);
    $router->get('/api/guide/locations/available', [GuideDashboardController::class, 'getAvailableLocations']);
    $router->get('/api/guide/locations/coverage', [GuideDashboardController::class, 'getGuideLocations']);
    $router->put('/api/guide/locations/update', [GuideDashboardController::class, 'updateGuideLocations']);
    $router->get('/api/guide/locations/check-removal/:location_name', [GuideDashboardController::class, 'checkLocationRemoval']);
    $router->get('/api/guide/bookings', [GuideDashboardController::class, 'getGuideBookings']);
    $router->put('/api/guide/bookings/:booking_id/mark-finished', [GuideDashboardController::class, 'markBookingAsFinished']);
    $router->post('/api/guide/test-notifications', [GuideDashboardController::class, 'createTestNotifications']);

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
    $router->get('/api/admin/verifications/pending/customers', [AdminController::class, 'getPendingCustomerVerifications']);
    $router->get('/api/admin/verifications/pending/renters', [AdminController::class, 'getPendingRenterVerifications']);
    $router->get('/api/admin/verifications/pending/guides', [AdminController::class, 'getPendingGuideVerifications']);
    $router->post('/api/admin/verifications/create-test-data', [AdminController::class, 'createTestData']);
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

    // Cart endpoints
    $router->get('/api/cart', [CartController::class, 'getOrCreateCart']);
    $router->post('/api/cart', [CartController::class, 'createCart']);
    $router->put('/api/cart/item/quantity', [CartController::class, 'updateItemQuantity']);
    $router->delete('/api/cart/item', [CartController::class, 'removeItem']);

    // Booking endpoints
    $router->get('/api/bookings/:id', [BookingController::class, 'show']);
    $router->post('/api/bookings', [BookingController::class, 'create']);
    $router->post('/api/bookings/confirm-payment', [BookingController::class, 'confirmPayment']);
    $router->post('/api/bookings/cancel-payment', [BookingController::class, 'cancelPayment']);
    $router->get('/api/bookings/payment-status/:orderId', [BookingController::class, 'getPaymentStatus']);
    $router->get('/api/bookings/payment-details/:id', [BookingController::class, 'getPaymentDetails']);
    $router->post('/api/payment/notify', [BookingController::class, 'handleWebhook']);

    // Debug endpoint to check session status
    $router->get('/api/debug/session', function (Request $request, Response $response) {
        $session = new Session();
        $response->json([
            'success' => true,
            'session_id' => session_id(),
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE,
            'user_id' => $session->get('user_id'),
            'headers' => getallheaders()
        ]);
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
