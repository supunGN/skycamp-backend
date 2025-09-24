<?php

/**
 * Travel Buddy Controller
 * Handles travel buddy feature for customers only
 * Requires travel_buddy_status = 'Active' in customers table
 */

class TravelBuddyController extends Controller
{
    private CustomerRepository $customerRepository;
    private TravelPlanRepository $travelPlanRepository;
    private TravelRequestRepository $travelRequestRepository;
    private TravelChatRepository $travelChatRepository;
    private TravelMessageRepository $travelMessageRepository;
    private ReminderRepository $reminderRepository;

    public function __construct()
    {
        parent::__construct(); // Initialize parent Controller (Request, Response, Session)
        $this->customerRepository = new CustomerRepository();
        $this->travelPlanRepository = new TravelPlanRepository();
        $this->travelRequestRepository = new TravelRequestRepository();
        $this->travelChatRepository = new TravelChatRepository();
        $this->travelMessageRepository = new TravelMessageRepository();
        $this->reminderRepository = new ReminderRepository();
    }

    /**
     * Check if user can access travel buddy features
     */
    private function checkTravelBuddyAccess(Request $request, Response $response): ?Customer
    {
        // Get current user from session
        $userId = $this->session->get('user_id');

        if (!$userId) {
            $response->setStatusCode(401);
            $response->json([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            return null;
        }

        // Get user details
        $userRepository = new UserRepository();
        $user = $userRepository->findById($userId);

        if (!$user) {
            $response->setStatusCode(401);
            $response->json([
                'success' => false,
                'message' => 'User not found'
            ]);
            return null;
        }

        // Check if user is a customer
        if ($user->role !== 'Customer') {
            $response->setStatusCode(403);
            $response->json([
                'success' => false,
                'message' => 'Travel Buddy feature is only available for customers'
            ]);
            return null;
        }

        // Get customer details and check travel buddy status
        $customer = $this->customerRepository->findByUserId($userId);

        if (!$customer) {
            $response->setStatusCode(403);
            $response->json([
                'success' => false,
                'message' => 'Customer profile not found'
            ]);
            return null;
        }

        // If Inactive, check if there is an unverified activation older than 48 hours and auto-expire
        if ($customer->travelBuddyStatus === 'Active' && ($customer->verificationStatus ?? 'No') !== 'Yes') {
            try {
                $reminder = $this->reminderRepository->getLatestByUserAndReason((int)$userId, 'TravelBuddyVerificationWindow');
                if ($reminder && isset($reminder['created_at'])) {
                    $createdAt = strtotime($reminder['created_at']);
                    if ($createdAt && (time() - $createdAt) > 48 * 3600) {
                        // Auto-expire: set inactive
                        $this->customerRepository->updateTravelBuddyStatusByUserId($userId, 'Inactive');
                        $customer->travelBuddyStatus = 'Inactive';
                    }
                }
            } catch (Exception $e) {
                // best-effort; ignore
            }
        }

        if ($customer->travelBuddyStatus !== 'Active') {
            $response->setStatusCode(403);
            $response->json([
                'success' => false,
                'message' => 'Travel Buddy feature is not enabled. Please enable it in your profile settings.'
            ]);
            return null;
        }

        return $customer;
    }

    /**
     * List travel plans
     * GET /api/travel-plans
     */
    public function listPlans(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 10);
            $destination = $params['destination'] ?? null;
            $travelDate = $params['travel_date'] ?? null;

            $plans = $this->travelPlanRepository->findAll($page, $limit, [
                'destination' => $destination,
                'travel_date' => $travelDate
            ]);

            $response->json([
                'success' => true,
                'message' => 'Travel plans retrieved successfully',
                'data' => [
                    'plans' => $plans,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $this->travelPlanRepository->count($params)
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to retrieve travel plans',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a new travel plan
     * POST /api/travel-plans
     */
    public function createPlan(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $data = $request->json();

            // Validate required fields
            $validator = new Validator();
            $errors = $validator->validateTravelPlan($data);

            if (!empty($errors)) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
                return;
            }

            // Create travel plan
            $planData = [
                'customer_id' => $customer->customerId,
                'destination' => $data['destination'],
                'travel_date' => $data['travel_date'],
                'notes' => $data['notes'] ?? null,
                'companions_needed' => (int) $data['companions_needed'],
                'companions_joined' => 0
            ];

            $plan = $this->travelPlanRepository->create($planData);

            // Create chat for this plan
            $chatData = [
                'plan_id' => $plan->planId
            ];
            $chat = $this->travelChatRepository->create($chatData);

            // Add plan creator as chat member
            $this->travelChatRepository->addMember($chat->chatId, $customer->customerId);

            $response->setStatusCode(201);
            $response->json([
                'success' => true,
                'message' => 'Travel plan created successfully',
                'data' => [
                    'plan' => $plan,
                    'chat_id' => $chat->chatId
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to create travel plan',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Request to join a travel plan
     * POST /api/travel-requests
     */
    public function requestJoin(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $data = $request->getBody();

            // Validate required fields
            if (empty($data['plan_id'])) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Plan ID is required'
                ]);
                return;
            }

            $planId = (int) $data['plan_id'];

            // Check if plan exists
            $plan = $this->travelPlanRepository->findById($planId);
            if (!$plan) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel plan not found'
                ]);
                return;
            }

            // Check if user is trying to join their own plan
            if ($plan->customerId === $customer->customerId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Cannot request to join your own travel plan'
                ]);
                return;
            }

            // Check if already requested
            $existingRequest = $this->travelRequestRepository->findByPlanAndRequester($planId, $customer->customerId);
            if ($existingRequest) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'You have already requested to join this travel plan'
                ]);
                return;
            }

            // Check if plan is full
            if ($plan->companionsJoined >= $plan->companionsNeeded) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'This travel plan is full'
                ]);
                return;
            }

            // Create join request
            $requestData = [
                'plan_id' => $planId,
                'requester_id' => $customer->customerId,
                'status' => 'Pending'
            ];

            $joinRequest = $this->travelRequestRepository->create($requestData);

            $response->setStatusCode(201);
            $response->json([
                'success' => true,
                'message' => 'Join request sent successfully',
                'data' => [
                    'request' => $joinRequest
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to send join request',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * List messages for a travel plan chat
     * GET /api/travel-messages
     */
    public function listMessages(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $params = $request->getQueryParams();
            $planId = (int) ($params['plan_id'] ?? 0);

            if (!$planId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Plan ID is required'
                ]);
                return;
            }

            // Check if customer is part of this plan's chat
            $chat = $this->travelChatRepository->findByPlanId($planId);
            if (!$chat) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Chat not found for this travel plan'
                ]);
                return;
            }

            $isMember = $this->travelChatRepository->isMember($chat->chatId, $customer->customerId);
            if (!$isMember) {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'You are not a member of this chat'
                ]);
                return;
            }

            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 50);

            $messages = $this->travelMessageRepository->findByChatId($chat->chatId, $page, $limit);

            $response->json([
                'success' => true,
                'message' => 'Messages retrieved successfully',
                'data' => [
                    'messages' => $messages,
                    'chat_id' => $chat->chatId,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to retrieve messages',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send a message to travel plan chat
     * POST /api/travel-messages
     */
    public function sendMessage(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $data = $request->getBody();

            // Validate required fields
            if (empty($data['plan_id']) || empty($data['message'])) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Plan ID and message are required'
                ]);
                return;
            }

            $planId = (int) $data['plan_id'];
            $messageText = trim($data['message']);

            if (strlen($messageText) > 1000) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Message is too long (max 1000 characters)'
                ]);
                return;
            }

            // Check if customer is part of this plan's chat
            $chat = $this->travelChatRepository->findByPlanId($planId);
            if (!$chat) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Chat not found for this travel plan'
                ]);
                return;
            }

            $isMember = $this->travelChatRepository->isMember($chat->chatId, $customer->customerId);
            if (!$isMember) {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'You are not a member of this chat'
                ]);
                return;
            }

            // Create message
            $messageData = [
                'chat_id' => $chat->chatId,
                'sender_id' => $customer->customerId,
                'message' => $messageText
            ];

            $message = $this->travelMessageRepository->create($messageData);

            $response->setStatusCode(201);
            $response->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message' => $message
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test endpoint to debug session issues
     * GET /api/travel-buddy/debug
     */
    public function debug(Request $request, Response $response): void
    {
        $userId = $this->session->get('user_id');
        $debugInfo = [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE,
            'headers' => getallheaders(),
            'user_id_from_session' => $userId,
            'authenticated' => $this->session->isAuthenticated(),
            'session_type' => $this->session->getSessionType(),
            'user_role' => $this->session->get('user_role')
        ];

        // Add user and customer info if available
        if ($userId) {
            try {
                $userRepository = new UserRepository();
                $user = $userRepository->findById($userId);

                if ($user) {
                    $debugInfo['user_info'] = [
                        'user_id' => $user->userId,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => $user->isActive
                    ];

                    // Add customer info if user is a customer
                    if ($user->role === 'Customer') {
                        $customer = $this->customerRepository->findByUserId($userId);
                        if ($customer) {
                            $debugInfo['customer_info'] = [
                                'customer_id' => $customer->customerId,
                                'first_name' => $customer->firstName,
                                'last_name' => $customer->lastName,
                                'travel_buddy_status' => $customer->travelBuddyStatus,
                                'verification_status' => $customer->verificationStatus
                            ];
                        } else {
                            $debugInfo['customer_info'] = 'Customer profile not found';
                        }
                    }
                } else {
                    $debugInfo['user_info'] = 'User not found';
                }
            } catch (Exception $e) {
                $debugInfo['error'] = 'Failed to fetch user/customer info: ' . $e->getMessage();
            }
        }

        $response->json([
            'success' => true,
            'debug' => $debugInfo
        ]);
    }

    /**
     * Get travel buddy status for current customer
     * GET /api/travel-buddy/status
     */
    public function getStatus(Request $request, Response $response): void
    {
        $userId = $this->session->get('user_id');

        // Debug logging
        error_log("TravelBuddyController::getStatus - User ID from session: " . ($userId ?? 'null'));
        error_log("TravelBuddyController::getStatus - Session data: " . print_r($_SESSION, true));
        error_log("TravelBuddyController::getStatus - Request headers: " . print_r(getallheaders(), true));
        error_log("TravelBuddyController::getStatus - Cookies: " . print_r($_COOKIE, true));

        if (!$userId) {
            error_log("TravelBuddyController::getStatus - No user ID found in session");
            $response->setStatusCode(401);
            $response->json([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            return;
        }

        try {
            $userRepository = new UserRepository();
            $user = $userRepository->findById($userId);

            error_log("TravelBuddyController::getStatus - User found: " . ($user ? 'yes' : 'no'));
            if ($user) {
                error_log("TravelBuddyController::getStatus - User role: " . $user->role);
            }

            if (!$user || $user->role !== 'Customer') {
                error_log("TravelBuddyController::getStatus - User is not a customer");
                $response->json([
                    'success' => true,
                    'data' => [
                        'available' => false,
                        'reason' => 'Travel Buddy is only available for customers'
                    ]
                ]);
                return;
            }

            $customer = $this->customerRepository->findByUserId($userId);

            error_log("TravelBuddyController::getStatus - Customer found: " . ($customer ? 'yes' : 'no'));
            if ($customer) {
                error_log("TravelBuddyController::getStatus - Customer travel buddy status: " . ($customer->travelBuddyStatus ?? 'null'));
            }

            if (!$customer) {
                error_log("TravelBuddyController::getStatus - No customer profile found");
                $response->json([
                    'success' => true,
                    'data' => [
                        'available' => false,
                        'reason' => 'Customer profile not found'
                    ]
                ]);
                return;
            }

            $isEnabled = $customer->travelBuddyStatus === 'Active';
            error_log("TravelBuddyController::getStatus - Travel buddy enabled: " . ($isEnabled ? 'yes' : 'no'));

            $response->json([
                'success' => true,
                'data' => [
                    'available' => true,
                    'enabled' => $isEnabled,
                    'status' => $customer->travelBuddyStatus
                ]
            ]);
        } catch (Exception $e) {
            error_log("TravelBuddyController::getStatus - Exception: " . $e->getMessage());
            error_log("TravelBuddyController::getStatus - Stack trace: " . $e->getTraceAsString());
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to get travel buddy status',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get travel plan details
     * GET /api/travel-plans/:id
     */
    public function getPlan(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $planId = (int) $request->get('id');

            if (!$planId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Plan ID is required'
                ]);
                return;
            }

            $plan = $this->travelPlanRepository->findById($planId);
            if (!$plan) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel plan not found'
                ]);
                return;
            }

            // Get chat info
            $chat = $this->travelChatRepository->findByPlanId($planId);
            $chatInfo = null;
            if ($chat) {
                $chatInfo = $this->travelChatRepository->getChatInfo($chat->chatId);
            }

            // Get pending requests if user owns the plan
            $pendingRequests = [];
            if ($plan->customerId === $customer->customerId) {
                $pendingRequests = $this->travelRequestRepository->findByPlanId($planId, 'Pending');
            }

            $response->json([
                'success' => true,
                'message' => 'Travel plan retrieved successfully',
                'data' => [
                    'plan' => $plan->toArray(),
                    'chat' => $chatInfo,
                    'pending_requests' => $pendingRequests
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to retrieve travel plan',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Accept a travel request
     * POST /api/travel-requests/:id/accept
     */
    public function acceptRequest(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $requestId = (int) $request->get('id');

            if (!$requestId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Request ID is required'
                ]);
                return;
            }

            // Get the request
            $travelRequest = $this->travelRequestRepository->findById($requestId);
            if (!$travelRequest) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel request not found'
                ]);
                return;
            }

            // Get the plan
            $plan = $this->travelPlanRepository->findById($travelRequest->planId);
            if (!$plan) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel plan not found'
                ]);
                return;
            }

            // Check if user owns the plan
            if ($plan->customerId !== $customer->customerId) {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'You can only accept requests for your own travel plans'
                ]);
                return;
            }

            // Check if plan is full
            if ($plan->companionsJoined >= $plan->companionsNeeded) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'This travel plan is full'
                ]);
                return;
            }

            // Accept the request
            if ($this->travelRequestRepository->acceptRequest($requestId)) {
                $response->json([
                    'success' => true,
                    'message' => 'Travel request accepted successfully'
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to accept travel request'
                ]);
            }
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to accept travel request',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reject a travel request
     * POST /api/travel-requests/:id/reject
     */
    public function rejectRequest(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $requestId = (int) $request->get('id');

            if (!$requestId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Request ID is required'
                ]);
                return;
            }

            // Get the request
            $travelRequest = $this->travelRequestRepository->findById($requestId);
            if (!$travelRequest) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel request not found'
                ]);
                return;
            }

            // Get the plan
            $plan = $this->travelPlanRepository->findById($travelRequest->planId);
            if (!$plan) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel plan not found'
                ]);
                return;
            }

            // Check if user owns the plan
            if ($plan->customerId !== $customer->customerId) {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'You can only reject requests for your own travel plans'
                ]);
                return;
            }

            // Reject the request
            if ($this->travelRequestRepository->rejectRequest($requestId)) {
                $response->json([
                    'success' => true,
                    'message' => 'Travel request rejected successfully'
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to reject travel request'
                ]);
            }
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to reject travel request',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user's travel plans
     * GET /api/travel-plans/my
     */
    public function getMyPlans(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 10);

            $plans = $this->travelPlanRepository->findByCustomerId($customer->customerId);

            $response->json([
                'success' => true,
                'message' => 'My travel plans retrieved successfully',
                'data' => [
                    'plans' => $plans,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($plans)
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to retrieve my travel plans',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user's travel requests
     * GET /api/travel-requests/my
     */
    public function getMyRequests(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 10);

            $requests = $this->travelRequestRepository->findByRequesterId($customer->customerId);

            $response->json([
                'success' => true,
                'message' => 'My travel requests retrieved successfully',
                'data' => [
                    'requests' => $requests,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($requests)
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to retrieve my travel requests',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update travel plan
     * PUT /api/travel-plans/:id
     */
    public function updatePlan(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $planId = (int) $request->get('id');
            $data = $request->json();

            if (!$planId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Plan ID is required'
                ]);
                return;
            }

            // Check if plan exists and user owns it
            $plan = $this->travelPlanRepository->findById($planId);
            if (!$plan) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel plan not found'
                ]);
                return;
            }

            if ($plan->customerId !== $customer->customerId) {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'You can only update your own travel plans'
                ]);
                return;
            }

            // Validate data
            $validator = new Validator();
            $errors = $validator->validateTravelPlan($data);

            if (!empty($errors)) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
                return;
            }

            // Update plan
            $updateData = [
                'destination' => $data['destination'],
                'travel_date' => $data['travel_date'],
                'notes' => $data['notes'] ?? null,
                'companions_needed' => (int) $data['companions_needed']
            ];

            if ($this->travelPlanRepository->update($planId, $updateData)) {
                $updatedPlan = $this->travelPlanRepository->findById($planId);
                $response->json([
                    'success' => true,
                    'message' => 'Travel plan updated successfully',
                    'data' => [
                        'plan' => $updatedPlan
                    ]
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to update travel plan'
                ]);
            }
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to update travel plan',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete travel plan
     * DELETE /api/travel-plans/:id
     */
    public function deletePlan(Request $request, Response $response): void
    {
        $customer = $this->checkTravelBuddyAccess($request, $response);
        if (!$customer) return;

        try {
            $planId = (int) $request->get('id');

            if (!$planId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Plan ID is required'
                ]);
                return;
            }

            // Check if plan exists and user owns it
            $plan = $this->travelPlanRepository->findById($planId);
            if (!$plan) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Travel plan not found'
                ]);
                return;
            }

            if ($plan->customerId !== $customer->customerId) {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'You can only delete your own travel plans'
                ]);
                return;
            }

            // Delete plan (cascade will handle related records)
            if ($this->travelPlanRepository->delete($planId)) {
                $response->json([
                    'success' => true,
                    'message' => 'Travel plan deleted successfully'
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to delete travel plan'
                ]);
            }
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to delete travel plan',
                'error' => $e->getMessage()
            ]);
        }
    }
}
