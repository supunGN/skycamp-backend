<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Renter.php';
require_once __DIR__ . '/../models/Guide.php';
require_once __DIR__ . '/../utils/SessionManager.php';

class LoginController
{
    private $db;
    private $user;
    private $customer;
    private $renter;
    private $guide;
    private $sessionManager;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->user = new User();
        $this->customer = new Customer($this->db);
        $this->renter = new Renter($this->db);
        $this->guide = new Guide($this->db);
        $this->sessionManager = new SessionManager();
    }

    public function login($email, $password)
    {
        try {
            // Validate input
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Email and password are required',
                    'data' => null,
                    'errors' => [
                        'email' => empty($email) ? 'Email is required' : null,
                        'password' => empty($password) ? 'Password is required' : null
                    ]
                ];
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format',
                    'data' => null,
                    'errors' => ['email' => 'Please enter a valid email address']
                ];
            }

            // Check if user exists
            if (!$this->user->emailExists($email)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password',
                    'data' => null,
                    'errors' => ['credentials' => 'Invalid email or password']
                ];
            }

            // Get user by email
            $userData = $this->user->getUserByEmail($email);
            if (!$userData) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password',
                    'data' => null,
                    'errors' => ['credentials' => 'Invalid email or password']
                ];
            }

            // Verify password
            if (!password_verify($password, $userData['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password',
                    'data' => null,
                    'errors' => ['credentials' => 'Invalid email or password']
                ];
            }

            // Check if user is active
            if ($userData['is_active'] != 1) {
                return [
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                    'data' => null,
                    'errors' => ['account' => 'Account deactivated']
                ];
            }

            // Get role-specific data
            $roleData = null;
            $redirectUrl = '/';

            switch ($userData['role']) {
                case 'Customer':
                    $roleData = $this->customer->getByUserId($userData['user_id']);
                    $redirectUrl = '/profile';
                    break;

                case 'Renter':
                    $roleData = $this->renter->getByUserId($userData['user_id']);
                    $redirectUrl = '/dashboard/renter';
                    break;

                case 'Guide':
                    $roleData = $this->guide->getByUserId($userData['user_id']);
                    $redirectUrl = '/dashboard/guide';
                    break;

                case 'Admin':
                    $redirectUrl = '/admin/dashboard';
                    break;

                default:
                    $redirectUrl = '/';
                    break;
            }

            // Prepare session data
            $sessionData = [
                'user_id' => $userData['user_id'],
                'email' => $userData['email'],
                'role' => strtolower($userData['role']),
                'is_active' => $userData['is_active'],
                'created_at' => $userData['created_at']
            ];

            // Add role-specific data to session
            if ($roleData) {
                switch ($userData['role']) {
                    case 'Customer':
                        $sessionData['customer_id'] = $roleData['customer_id'];
                        $sessionData['first_name'] = $roleData['first_name'];
                        $sessionData['last_name'] = $roleData['last_name'];
                        $sessionData['full_name'] = $roleData['first_name'] . ' ' . $roleData['last_name'];
                        $sessionData['user_role'] = 'customer';
                        $sessionData['travel_buddy_status'] = $roleData['travel_buddy_status'];
                        $sessionData['verification_status'] = $roleData['verification_status'];
                        break;

                    case 'Renter':
                        $sessionData['renter_id'] = $roleData['renter_id'];
                        $sessionData['first_name'] = $roleData['first_name'];
                        $sessionData['last_name'] = $roleData['last_name'];
                        $sessionData['full_name'] = $roleData['first_name'] . ' ' . $roleData['last_name'];
                        $sessionData['user_role'] = 'service_provider';
                        $sessionData['provider_type'] = 'Equipment Renter';
                        $sessionData['district'] = $roleData['district'];
                        $sessionData['verification_status'] = $roleData['verification_status'];
                        break;

                    case 'Guide':
                        $sessionData['guide_id'] = $roleData['guide_id'];
                        $sessionData['first_name'] = $roleData['first_name'];
                        $sessionData['last_name'] = $roleData['last_name'];
                        $sessionData['full_name'] = $roleData['first_name'] . ' ' . $roleData['last_name'];
                        $sessionData['user_role'] = 'service_provider';
                        $sessionData['provider_type'] = 'Local Guide';
                        $sessionData['district'] = $roleData['district'];
                        $sessionData['verification_status'] = $roleData['verification_status'];
                        $sessionData['price_per_day'] = $roleData['price_per_day'];
                        $sessionData['languages'] = $roleData['languages'];
                        break;
                }
            }

            // Start session
            $sessionResult = $this->sessionManager->createUserSession($sessionData);
            if (!$sessionResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to create session. Please try again.',
                    'data' => null,
                    'errors' => ['session' => 'Session creation failed']
                ];
            }

            // Update last login time (optional enhancement)
            $this->user->updateLastLogin($userData['user_id']);

            return [
                'success' => true,
                'message' => 'Login successful! Welcome back.',
                'data' => [
                    'session_id' => $sessionResult['session_id'],
                    'redirect_url' => $redirectUrl
                ],
                'errors' => [],
                'user' => $sessionData
            ];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'A system error occurred. Please contact support.',
                'data' => null,
                'errors' => ['system' => 'Internal server error']
            ];
        }
    }

    public function logout()
    {
        try {
            $result = $this->sessionManager->destroySession();

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Logged out successfully',
                    'data' => ['redirect_url' => '/'],
                    'errors' => []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Logout failed. Please try again.',
                    'data' => null,
                    'errors' => ['system' => 'Session destruction failed']
                ];
            }
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Logout failed. Please try again.',
                'data' => null,
                'errors' => ['system' => 'Internal server error']
            ];
        }
    }
}
