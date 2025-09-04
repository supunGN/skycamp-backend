<?php

/**
 * Renter Registration Controller Class
 * Handles renter-specific registration logic
 * Extends BaseRegistrationController for shared functionality
 */

require_once __DIR__ . '/BaseRegistrationController.php';
require_once __DIR__ . '/../models/Renter.php';

class RenterRegistrationController extends BaseRegistrationController
{
    private $renter_model;

    /**
     * Constructor - Initialize renter-specific models
     */
    public function __construct()
    {
        parent::__construct();
        // Initialize renter model with database connection
        $database = new Database();
        $this->renter_model = new Renter($database->getConnection());
    }

    /**
     * Handle renter registration
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

            // Validate input data for renter
            $validation_result = $this->validateRenterData($data);
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
            if ($this->renter_model->nicExists($data['nicNumber'])) {
                $this->response['message'] = 'NIC number is already registered';
                $this->response['errors']['nicNumber'] = 'This NIC number is already in use';
                return $this->response;
            }

            // Create user account with Renter role
            $user_id = $this->createUserAccount($data, 'Renter');
            if (!$user_id) {
                throw new Exception('Failed to create user account');
            }

            // Handle file uploads (optional)
            $file_paths = $this->handleFileUploads($files, $user_id);

            // Create renter profile
            $renter_id = $this->createRenterProfile($data, $user_id, $file_paths);
            if (!$renter_id) {
                throw new Exception('Failed to create renter profile');
            }

            // Commit transaction
            $conn->commit();

            // Start user session
            $this->startRenterSession($user_id);

            // Prepare success response
            $this->response['success'] = true;
            $this->response['message'] = 'Renter registration successful! Welcome to SkyCamp.';
            $this->response['data'] = [
                'user_id' => $user_id,
                'renter_id' => $renter_id,
                'redirect_url' => '/dashboard/renter'
            ];

            // Get complete user data for session
            $user_data = $this->getRenterUserData($user_id);
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

            error_log("Renter registration error: " . $e->getMessage());
            $this->response['message'] = 'Registration failed. Please try again.';
            $this->response['errors']['general'] = $e->getMessage();

            return $this->response;
        }
    }

    /**
     * Validate renter registration data
     * 
     * @param array $data Form data
     * @return array Validation result
     */
    private function validateRenterData($data)
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
            'district'
        ];

        $errors = $this->validateBasicFields($data, $required_fields);

        // Renter-specific age validation (18+ years)
        if (isset($data['dob'])) {
            $age_error = $this->validateAge($data['dob'], 18);
            if ($age_error) {
                $errors['dob'] = $age_error . ' to register as a renter';
            }
        }

        // District validation
        if (isset($data['district']) && empty(trim($data['district']))) {
            $errors['district'] = 'District is required';
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
     * Create renter profile in database
     * 
     * @param array $data Form data
     * @param string $user_id User ID
     * @param array $file_paths Uploaded file paths
     * @return string|false Renter ID on success, false on failure
     */
    private function createRenterProfile($data, $user_id, $file_paths)
    {
        $renter_data = [
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
            'latitude' => isset($data['latitude']) ? $data['latitude'] : null,
            'longitude' => isset($data['longitude']) ? $data['longitude'] : null
        ];

        $result = $this->renter_model->create($renter_data);

        if ($result['success']) {
            return $result['renter_id'];
        }

        return false;
    }

    /**
     * Start renter session after successful registration
     * 
     * @param string $user_id User ID
     */
    private function startRenterSession($user_id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_role'] = 'service_provider';
        $_SESSION['provider_type'] = 'Equipment Renter';
    }

    /**
     * Get complete renter user data for response
     * 
     * @param string $user_id User ID
     * @return array|null User data or null if not found
     */
    private function getRenterUserData($user_id)
    {
        $renter_data = $this->renter_model->getByUserId($user_id);
        $user_data = $this->user_model->getUserById($user_id);

        if ($renter_data && $user_data) {
            return [
                'user_id' => $user_data['user_id'],
                'renter_id' => $renter_data['renter_id'],
                'email' => $user_data['email'],
                'first_name' => $renter_data['first_name'],
                'last_name' => $renter_data['last_name'],
                'full_name' => $renter_data['first_name'] . ' ' . $renter_data['last_name'],
                'user_role' => 'service_provider',
                'provider_type' => 'Equipment Renter',
                'is_active' => $user_data['is_active'],
                'verification_status' => $renter_data['verification_status']
            ];
        }

        return null;
    }
}
