<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Customer.php';
require_once '../classes/ServiceProvider.php';
require_once '../classes/FileUpload.php';

class AuthController
{
    private $fileUpload;

    public function __construct()
    {
        $this->fileUpload = new FileUpload();
    }

    public function register()
    {
        try {
            // Debug POST and FILES
            file_put_contents(
                __DIR__ . '/../debug_register.log',
                "POST: " . print_r($_POST, true) . "\nFILES: " . print_r($_FILES, true) . "\n",
                FILE_APPEND
            );

            $input = $_POST;
            $this->validateRegistrationData($input);

            // Map service provider fields
            if ($input['userRole'] === 'service_provider') {
                $input['provider_type'] = $input['serviceRole'] ?? 'Equipment Renter';

                $input['camping_locations'] = !empty($input['campingLocations'])
                    ? json_encode(array_map('trim', explode(',', $input['campingLocations'])))
                    : json_encode([]);

                $input['stargazing_locations'] = !empty($input['stargazingLocations'])
                    ? json_encode(array_map('trim', explode(',', $input['stargazingLocations'])))
                    : json_encode([]);

                $input['available_districts'] = !empty($input['districts'])
                    ? json_encode(array_map('trim', explode(',', $input['districts'])))
                    : json_encode([]);

                unset($input['serviceRole'], $input['campingLocations'], $input['stargazingLocations'], $input['districts']);
            }

            // Determine user type
            $user = ($input['userRole'] === 'customer') ? new Customer() : new ServiceProvider();

            if ($user->emailExists($input['email'])) {
                throw new Exception("Email already exists");
            }

            // Handle file uploads
            $input = $this->handleFileUploads($input);

            // Register user
            $userId = $user->register($input);

            // Fetch minimal user data for response
            if ($input['userRole'] === 'customer') {
                $userData = Customer::getById($userId);
            } else {
                $userData = ServiceProvider::getById($userId);
            }
            if (!empty($userData) && !isset($userData['user_role'])) {
                $userData['user_role'] = $input['userRole'];
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = $input['userRole'];
            $_SESSION['user_name'] = $input['firstName'] . ' ' . $input['lastName'];

            $this->sendResponse([
                'success' => true,
                'message' => 'Registration successful',
                'user' => $userData,
                'redirect_url' => '/dashboard'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }



    /**
     * Login user
     */
    public function login()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['email']) || empty($input['password'])) {
                throw new Exception("Email and password are required");
            }

            // Try Customer Login
            $customer = new Customer();
            $result = $customer->login($input['email'], $input['password']);

            if ($result['success']) {
                $userData = Customer::getByEmail($input['email']);
                if (!empty($userData) && !isset($userData['user_role'])) {
                    $userData['user_role'] = 'customer';
                }

                $this->sendResponse([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $userData,
                    'redirect_url' => '/profile'
                ]);
                return;
            }

            // Try Service Provider Login
            $serviceProvider = new ServiceProvider();
            $result = $serviceProvider->login($input['email'], $input['password']);

            if ($result['success']) {
                $userData = ServiceProvider::getByEmail($input['email']);
                if (!empty($userData) && !isset($userData['user_role'])) {
                    $userData['user_role'] = 'service_provider';
                }

                $redirectUrl = '/dashboard';
                if (isset($userData['provider_type'])) {
                    if ($userData['provider_type'] === 'Equipment Renter') {
                        $redirectUrl = '/dashboard/renter/overview';
                    } elseif ($userData['provider_type'] === 'Local Guide') {
                        $redirectUrl = '/dashboard/guide/overview';
                    }
                }

                $this->sendResponse([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $userData,
                    'redirect_url' => $redirectUrl
                ]);
                return;
            }

            // Login failed
            $this->sendResponse([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }




    /**
     * Logout user
     */
    public function logout()
    {
        session_destroy();
        $this->sendResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['email'])) {
                throw new Exception("Email is required");
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);

            if (!$stmt->fetch()) {
                throw new Exception("Email not found");
            }

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $db->prepare("
                INSERT INTO password_resets (email, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                expires_at = VALUES(expires_at), 
                created_at = NOW()
            ");
            $stmt->execute([$input['email'], $token, $expires]);

            $this->sendResponse([
                'success' => true,
                'message' => 'Password reset link sent to your email',
                'token' => $token
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['token']) || empty($input['password'])) {
                throw new Exception("Token and password are required");
            }

            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare("
                SELECT email FROM password_resets 
                WHERE token = ? AND expires_at > NOW()
            ");
            $stmt->execute([$input['token']]);
            $reset = $stmt->fetch();

            if (!$reset) {
                throw new Exception("Invalid or expired token");
            }

            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $reset['email']]);

            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$input['token']]);

            $this->sendResponse([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData($data)
    {
        $required = [
            'firstName',
            'lastName',
            'email',
            'phone',
            'dateOfBirth',
            'gender',
            'address',
            'password',
            'userRole'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($data['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if (
            !preg_match('/[A-Z]/', $data['password']) ||
            !preg_match('/[a-z]/', $data['password']) ||
            !preg_match('/\d/', $data['password'])
        ) {
            throw new Exception("Password must include uppercase, lowercase, and a number.");
        }

        // Additional validation for service providers
        if ($data['userRole'] === 'service_provider') {
            if (empty($data['serviceRole'])) {
                throw new Exception("Service role (Equipment Renter or Local Guide) is required.");
            }
        }
    }


    /**
     * Handle file uploads
     */
    private function handleFileUploads($data)
    {
        $data['profilePicture'] = null;
        $data['nicFront'] = null;
        $data['nicBack'] = null;

        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $data['profilePicture'] = $this->fileUpload->uploadProfilePicture($_FILES['profilePicture']);
        }

        if (isset($_FILES['nicFront']) && $_FILES['nicFront']['error'] === UPLOAD_ERR_OK) {
            $data['nicFront'] = $this->fileUpload->uploadNicDocument($_FILES['nicFront'], 'front');
        }

        if (isset($_FILES['nicBack']) && $_FILES['nicBack']['error'] === UPLOAD_ERR_OK) {
            $data['nicBack'] = $this->fileUpload->uploadNicDocument($_FILES['nicBack'], 'back');
        }

        return $data;
    }

    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
