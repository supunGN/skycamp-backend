<?php

/**
 * Authentication Controller
 * Handles authentication-related API endpoints
 */

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * Register a new user
     * POST /api/auth/register
     */
    public function register(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->register($request);

            if ($result['success']) {
                $response->json([
                    'success' => true,
                    'user' => $result['user'],
                    'redirect_url' => $result['data']['redirect_url'] ?? '/'
                ], 201);
            } else {
                if (isset($result['errors'])) {
                    $response->validationError($result['errors']);
                } else {
                    $statusCode = strpos($result['message'], 'already') !== false ? 409 : 400;
                    $response->error($result['message'], $statusCode);
                }
            }
        } catch (Exception $e) {
            $this->log("Registration error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Registration failed');
        }
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request, Response $response): void
    {
        try {
            $email = $request->json('email');
            $password = $request->json('password');

            if (empty($email) || empty($password)) {
                $response->error('Email and password are required', 400);
                return;
            }

            $result = $this->authService->login($email, $password);

            if ($result['success']) {
                $response->json([
                    'success' => true,
                    'user' => $result['user'],
                    'redirect_url' => $result['data']['redirect_url'] ?? '/'
                ], 200);
            } else {
                $response->error($result['message'], 401);
            }
        } catch (Exception $e) {
            $this->log("Login error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Login failed');
        }
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->logout();

            if ($result['success']) {
                $response->success(null, 'Logout successful');
            } else {
                $response->error($result['message'], 500);
            }
        } catch (Exception $e) {
            $this->log("Logout error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Logout failed');
        }
    }

    /**
     * Get current user
     * GET /api/auth/me
     */
    public function me(Request $request, Response $response): void
    {
        try {
            $user = $this->authService->getCurrentUser();

            if ($user) {
                $response->json([
                    'success' => true,
                    'data' => [
                        'authenticated' => true,
                        'user' => $user
                    ]
                ], 200);
            } else {
                $response->json([
                    'success' => false,
                    'data' => [
                        'authenticated' => false,
                        'message' => 'Not authenticated'
                    ]
                ], 401);
            }
        } catch (Exception $e) {
            $this->log("Get user error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to get user');
        }
    }

    /**
     * Update user profile
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->updateProfile($request);

            if ($result['success']) {
                $response->json([
                    'success' => true,
                    'user' => $result['user'],
                    'message' => 'Profile updated successfully'
                ], 200);
            } else {
                if (isset($result['errors'])) {
                    $response->validationError($result['errors']);
                } else {
                    $response->error($result['message'], 400);
                }
            }
        } catch (Exception $e) {
            $this->log("Profile update error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Profile update failed');
        }
    }

    /**
     * Toggle Travel Buddy status for customer
     * POST /api/auth/travel-buddy/toggle
     */
    public function toggleTravelBuddy(Request $request, Response $response): void
    {
        try {
            // Get current user from session
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

            // Get user details
            $userRepository = new UserRepository();
            $user = $userRepository->findById($userId);
            
            if (!$user) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                return;
            }

            // Check if user is a customer
            if ($user->role !== 'Customer') {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'Travel Buddy feature is only available for customers'
                ]);
                return;
            }

            // Get request data
            $data = $request->getFormData();
            $status = $data['status'] ?? null;

            // Validate status
            if (!in_array($status, ['Active', 'Inactive'])) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Invalid status. Must be Active or Inactive'
                ]);
                return;
            }

            // Update travel buddy status
            $customerRepository = new CustomerRepository();
            $success = $customerRepository->updateTravelBuddyStatusByUserId($userId, $status);

            // If activating, record a reminder start for verification window
            if ($success && $status === 'Active') {
                try {
                    $reminderRepo = new ReminderRepository();
                    $reminderRepo->create((int)$userId, 'TravelBuddyVerificationWindow', 'Info');
                } catch (Exception $e) {
                    // Non-fatal – continue
                }
            }

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Travel Buddy status updated successfully',
                    'data' => [
                        'status' => $status,
                        'enabled' => $status === 'Active'
                    ]
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to update Travel Buddy status'
                ]);
            }
        } catch (Exception $e) {
            $this->log("Travel Buddy toggle error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to update Travel Buddy status');
        }
    }

    /**
     * Submit verification documents
     * POST /api/auth/verification/submit
     */
    public function submitVerification(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $session = new Session();
            $userId = $session->get('user_id');
            
            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in to submit verification documents'
                ]);
                return;
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
                return;
            }

            // Check if user is a customer
            if ($user->role !== 'Customer') {
                $response->setStatusCode(403);
                $response->json([
                    'success' => false,
                    'message' => 'Verification is only available for customers'
                ]);
                return;
            }

            // Get uploaded files
            $nicFrontFile = $request->file('nic_front_image');
            $nicBackFile = $request->file('nic_back_image');

            // Validate files
            if (!$nicFrontFile || !$nicBackFile) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Both NIC front and back images are required'
                ]);
                return;
            }

            // Validate file types and sizes
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($nicFrontFile['type'], $allowedTypes) || !in_array($nicBackFile['type'], $allowedTypes)) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'Only JPG, PNG, and WebP images are allowed'
                ]);
                return;
            }

            if ($nicFrontFile['size'] > $maxSize || $nicBackFile['size'] > $maxSize) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'File size must be less than 5MB'
                ]);
                return;
            }

            // Upload files
            $uploader = new Uploader();
            $nicFrontPath = $uploader->upload($nicFrontFile, 'verification');
            $nicBackPath = $uploader->upload($nicBackFile, 'verification');

            if (!$nicFrontPath || !$nicBackPath) {
                $errors = $uploader->getErrors();
                $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Failed to upload verification documents';
                
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
                return;
            }

            // Update customer record with NIC images and set verification status to Pending
            $customerRepository = new CustomerRepository();
            $success = $customerRepository->updateVerificationDocuments($userId, $nicFrontPath, $nicBackPath);

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Verification documents submitted successfully. Your verification is under review.',
                    'data' => [
                        'verification_status' => 'Pending',
                        'nic_front_image' => $nicFrontPath,
                        'nic_back_image' => $nicBackPath
                    ]
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to submit verification documents'
                ]);
            }
        } catch (Exception $e) {
            $this->log("Verification submit error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to submit verification documents');
        }
    }
}
