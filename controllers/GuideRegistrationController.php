<?php

/**
 * Guide Registration Controller Class
 * Handles guide-specific registration logic
 * Extends BaseRegistrationController for shared functionality
 */

require_once __DIR__ . '/BaseRegistrationController.php';
require_once __DIR__ . '/../models/Guide.php';

class GuideRegistrationController extends BaseRegistrationController
{
    private $guide_model;

    /**
     * Constructor - Initialize guide-specific models
     */
    public function __construct()
    {
        parent::__construct();
        // Initialize guide model with database connection
        $database = new Database();
        $this->guide_model = new Guide($database->getConnection());
    }

    /**
     * Handle guide registration
     * 
     * @param array $data Form data from $_POST
     * @param array $files File data from $_FILES
     * @return array Response array
     */
    public function register($data, $files = [])
    {
        try {
            // Start transaction for data integrity
            $database = new Database();
            $conn = $database->getConnection();
            $conn->beginTransaction();

            // Validate input data for guide
            $validation_result = $this->validateGuideData($data);
            if (!$validation_result['valid']) {
                $this->response['message'] = 'Validation failed';
                $this->response['errors'] = $validation_result['errors'];
                return $this->response;
            }

            // Check if email already exists
            if ($this->user_model->emailExists($data['email'])) {
                $this->response['message'] = 'Email address is already registered';
                $this->response['errors']['email'] = 'This email is already in use';
                return $this->response;
            }

            // Check if NIC already exists
            if ($this->guide_model->nicExists($data['nicNumber'])) {
                $this->response['message'] = 'NIC number is already registered';
                $this->response['errors']['nicNumber'] = 'This NIC number is already in use';
                return $this->response;
            }

            // Create user account with Guide role
            $user_id = $this->createUserAccount($data, 'Guide');
            if (!$user_id) {
                throw new Exception('Failed to create user account');
            }

            // Handle file uploads (optional)
            $file_paths = $this->handleFileUploads($files, $user_id);

            // Create guide profile
            $guide_id = $this->createGuideProfile($data, $user_id, $file_paths);
            if (!$guide_id) {
                throw new Exception('Failed to create guide profile');
            }

            // Commit transaction
            $conn->commit();

            // Start user session
            $this->startGuideSession($user_id);

            // Prepare success response
            $this->response['success'] = true;
            $this->response['message'] = 'Guide registration successful! Welcome to SkyCamp.';
            $this->response['data'] = [
                'user_id' => $user_id,
                'guide_id' => $guide_id,
                'redirect_url' => '/dashboard/guide'
            ];

            // Get complete user data for session
            $user_data = $this->getGuideUserData($user_id);
            $this->response['user'] = $user_data;

            return $this->response;
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($conn)) {
                $conn->rollback();
            }

            // Clean up uploaded files on error
            if (isset($file_paths)) {
                $this->cleanupUploadedFiles($file_paths);
            }

            error_log("Guide registration error: " . $e->getMessage());
            $this->response['message'] = 'Registration failed. Please try again.';
            $this->response['errors']['general'] = $e->getMessage();

            return $this->response;
        }
    }

    /**
     * Validate guide registration data
     * 
     * @param array $data Form data
     * @return array Validation result
     */
    private function validateGuideData($data)
    {
        $required_fields = [
            'email',
            'password',
            'confirmPassword',
            'firstName',
            'lastName',
            'dob',
            'phoneNumber',
            'homeAddress',
            'nicNumber',
            'gender',
            'district',
            'description',
            'pricePerDay',
            'languages'
        ];

        $errors = $this->validateBasicFields($data, $required_fields);

        // Guide-specific age validation (18+ years)
        if (isset($data['dob'])) {
            $age_error = $this->validateAge($data['dob'], 18);
            if ($age_error) {
                $errors['dob'] = $age_error . ' to register as a guide';
            }
        }

        // District validation
        if (isset($data['district']) && empty(trim($data['district']))) {
            $errors['district'] = 'District is required';
        }

        // Description validation
        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors['description'] = 'Description is too long (max 500 characters)';
        }

        // Price validation
        if (isset($data['pricePerDay'])) {
            if (!is_numeric($data['pricePerDay']) || floatval($data['pricePerDay']) <= 0) {
                $errors['pricePerDay'] = 'Price must be a valid positive number';
            }
        }

        // Currency validation
        if (isset($data['currency'])) {
            $valid_currencies = ['LKR', 'USD', 'EUR'];
            if (!in_array($data['currency'], $valid_currencies)) {
                $errors['currency'] = 'Invalid currency selection';
            }
        }

        // Service areas validation
        if (empty($data['campingDestinations']) && empty($data['stargazingSpots'])) {
            $errors['serviceAreas'] = 'Please select at least one camping destination or stargazing spot';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create guide profile in database
     * 
     * @param array $data Form data
     * @param string $user_id User ID
     * @param array $file_paths Uploaded file paths
     * @return string|false Guide ID on success, false on failure
     */
    private function createGuideProfile($data, $user_id, $file_paths)
    {
        $guide_data = [
            'user_id' => $user_id,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'dob' => $data['dob'],
            'phone_number' => $data['phoneNumber'],
            'home_address' => $data['homeAddress'],
            'gender' => $data['gender'],
            'nic_number' => strtoupper($data['nicNumber']),
            'camping_destinations' => isset($data['campingDestinations']) ? $data['campingDestinations'] : null,
            'stargazing_spots' => isset($data['stargazingSpots']) ? $data['stargazingSpots'] : null,
            'district' => $data['district'],
            'description' => $data['description'],
            'special_note' => isset($data['specialNote']) ? $data['specialNote'] : null,
            'currency' => isset($data['currency']) ? $data['currency'] : 'LKR',
            'languages' => $data['languages'],
            'price_per_day' => floatval($data['pricePerDay'])
        ];

        $result = $this->guide_model->create($guide_data);

        if ($result['success']) {
            return $result['guide_id'];
        }

        return false;
    }

    /**
     * Start guide session after successful registration
     * 
     * @param string $user_id User ID
     */
    private function startGuideSession($user_id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_role'] = 'service_provider';
        $_SESSION['provider_type'] = 'Local Guide';
    }

    /**
     * Get complete guide user data for response
     * 
     * @param string $user_id User ID
     * @return array|null User data or null if not found
     */
    private function getGuideUserData($user_id)
    {
        $guide_data = $this->guide_model->getByUserId($user_id);
        $user_data = $this->user_model->getUserById($user_id);

        if ($guide_data && $user_data) {
            return [
                'user_id' => $user_data['user_id'],
                'guide_id' => $guide_data['guide_id'],
                'email' => $user_data['email'],
                'first_name' => $guide_data['first_name'],
                'last_name' => $guide_data['last_name'],
                'full_name' => $guide_data['first_name'] . ' ' . $guide_data['last_name'],
                'user_role' => 'service_provider',
                'provider_type' => 'Local Guide',
                'is_active' => $user_data['is_active'],
                'verification_status' => $guide_data['verification_status']
            ];
        }

        return null;
    }
}
