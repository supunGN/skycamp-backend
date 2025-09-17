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
        $session = new Session();
        $userId = $session->get('user_id');
        
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
            $data = $request->getBody();
            
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
     * Get travel buddy status for current customer
     * GET /api/travel-buddy/status
     */
    public function getStatus(Request $request, Response $response): void
    {
        $session = new Session();
        $userId = $session->get('user_id');
        
        if (!$userId) {
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
            
            if (!$user || $user->role !== 'Customer') {
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
            
            if (!$customer) {
                $response->json([
                    'success' => true,
                    'data' => [
                        'available' => false,
                        'reason' => 'Customer profile not found'
                    ]
                ]);
                return;
            }

            $response->json([
                'success' => true,
                'data' => [
                    'available' => true,
                    'enabled' => $customer->travelBuddyStatus === 'Active',
                    'status' => $customer->travelBuddyStatus
                ]
            ]);
        } catch (Exception $e) {
            $response->setStatusCode(500);
            $response->json([
                'success' => false,
                'message' => 'Failed to get travel buddy status',
                'error' => $e->getMessage()
            ]);
        }
    }
}
