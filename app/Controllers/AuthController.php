<?php

use App\Services\NotificationService;

/**
 * Authentication Controller
 * Handles authentication-related API endpoints
 */

class AuthController extends Controller
{
    private AuthService $authService;
    private PDO $pdo;
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        $this->pdo = Database::getConnection();
        $this->notificationService = new NotificationService($this->pdo);
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
                // Return minimal session info for security
                $sessionInfo = [
                    'authenticated' => true,
                    'session_type' => $this->session->getSessionType(),
                    'user' => $user,
                    'security_info' => $this->session->getSecurityInfo()
                ];

                $response->json([
                    'success' => true,
                    'data' => $sessionInfo
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
            $userId = $this->session->get('user_id');

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
            $userId = $this->session->get('user_id');

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

            // Check if user is submitting existing NIC images or uploading new ones
            $customerRepository = new CustomerRepository();
            $customer = $customerRepository->findByUserId($userId);

            if (!$customer) {
                $response->setStatusCode(404);
                $response->json([
                    'success' => false,
                    'message' => 'Customer profile not found'
                ]);
                return;
            }

            // If no files uploaded, check if user has existing NIC images
            if (!$nicFrontFile || !$nicBackFile) {
                if (!$customer->nicFrontImage || !$customer->nicBackImage) {
                    $response->setStatusCode(400);
                    $response->json([
                        'success' => false,
                        'message' => 'Both NIC front and back images are required. Please upload them first.'
                    ]);
                    return;
                }

                // User has existing NIC images, submit them for verification
                $this->createVerificationRecord($userId, 'Pending');
                $this->notifyAdminForVerification($userId, 'customer');

                // Update verification status to Pending
                $customerRepository->updateVerificationStatus($userId, 'Pending');

                $response->json([
                    'success' => true,
                    'message' => 'Verification documents submitted successfully. Your verification is under review.',
                    'data' => [
                        'verification_status' => 'Pending',
                        'nic_front_image' => $customer->nicFrontImage,
                        'nic_back_image' => $customer->nicBackImage
                    ]
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

            // Upload files using FileService to standardized user folder
            $fileService = new FileService();
            $frontRes = $fileService->uploadNicFront($nicFrontFile, (string)$userId);
            $backRes = $fileService->uploadNicBack($nicBackFile, (string)$userId);

            $nicFrontPath = $frontRes['success'] ? ($frontRes['file_path'] ?? null) : null;
            $nicBackPath = $backRes['success'] ? ($backRes['file_path'] ?? null) : null;

            if (!$nicFrontPath || !$nicBackPath) {
                $errorMessage = $frontRes['message'] ?? $backRes['message'] ?? 'Failed to upload verification documents';

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
                // Create verification record and notify admin
                $this->createVerificationRecord($userId, 'Pending');
                $this->notifyAdminForVerification($userId, 'customer');

                // Send notification to user
                $this->notificationService->sendVerificationNotification($userId, 'pending');

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

    /**
     * Create verification record in user_verifications table
     */
    private function createVerificationRecord(string $userId, string $status): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_verifications (user_id, status, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$userId, $status]);
        } catch (Exception $e) {
            error_log("Failed to create verification record: " . $e->getMessage());
        }
    }

    /**
     * Notify admin about new verification request
     */
    private function notifyAdminForVerification(string $userId, string $userType): void
    {
        try {
            // Get user details for notification
            $stmt = $this->pdo->prepare("
                SELECT u.first_name, u.last_name, u.email 
                FROM users u 
                WHERE u.user_id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $message = "New verification request from {$user['first_name']} {$user['last_name']} ({$userType})";

                // Create notification for all admins
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (user_id, type, message, is_read, created_at) 
                    SELECT admin_id, 'Verification', ?, 0, NOW() 
                    FROM admins
                ");
                $stmt->execute([$message]);
            }
        } catch (Exception $e) {
            error_log("Failed to notify admin for verification: " . $e->getMessage());
        }
    }

    /**
     * Get current user's verification docs and status
     * GET /api/auth/verification/docs
     */
    public function getVerificationDocs(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            $customerRepository = new CustomerRepository();
            $customer = $customerRepository->findByUserId($userId);

            if (!$customer) {
                $response->json([
                    'success' => true,
                    'data' => [
                        'verification_status' => 'No',
                        'nic_front_image_url' => null,
                        'nic_back_image_url' => null,
                    ]
                ]);
                return;
            }

            $front = $customer->nicFrontImage ?? null;
            $back = $customer->nicBackImage ?? null;

            // Build public URLs from relative paths
            $buildUrl = function (?string $p): ?string {
                if (!$p) return null;

                // Handle different path formats
                $normalized = str_replace('\\', '/', $p);

                // If it contains storage/uploads/, extract the relative part
                $idx = strpos($normalized, 'storage/uploads/');
                if ($idx !== false) {
                    $relative = substr($normalized, $idx + strlen('storage/uploads/'));
                } else {
                    // If it's already a relative path, use it as is
                    $relative = ltrim($normalized, '/');
                }

                // Clean up the path
                $relative = ltrim($relative, '/');

                // Ensure the path is properly formatted
                if (!empty($relative)) {
                    return 'http://localhost/skycamp/skycamp-backend/public/storage/uploads/' . $relative;
                }

                return null;
            };

            // Get latest verification record to check for rejection details
            $stmt = $this->pdo->prepare("
                SELECT status, note, created_at 
                FROM user_verifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $verificationRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => [
                    'verification_status' => $customer->verificationStatus ?? 'No',
                    'nic_front_image_url' => $buildUrl($front),
                    'nic_back_image_url' => $buildUrl($back),
                    'rejection_reason' => $verificationRecord['status'] === 'Rejected' ? $verificationRecord['note'] : null,
                    'rejection_date' => $verificationRecord['status'] === 'Rejected' ? $verificationRecord['created_at'] : null,
                    'can_resubmit' => $verificationRecord['status'] === 'Rejected' || !$verificationRecord,
                ]
            ]);
        } catch (Exception $e) {
            $this->log('Get verification docs error: ' . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to get verification documents');
        }
    }
}
