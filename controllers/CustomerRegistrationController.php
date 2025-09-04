<?php

/**
 * Customer Registration Controller Class
 * Handles customer-specific registration logic
 * Extends BaseRegistrationController for shared functionality
 */

require_once __DIR__ . '/BaseRegistrationController.php';
require_once __DIR__ . '/../models/Customer.php';

class CustomerRegistrationController extends BaseRegistrationController
{
    private $customer_model;

    /**
     * Constructor - Initialize customer-specific models
     */
    public function __construct()
    {
        parent::__construct();
        $this->customer_model = new Customer();
    }

    /**
     * Handle customer registration
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

            // Validate input data
            $validation_result = $this->validateCustomerData($data);
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
            if ($this->customer_model->nicExists($data['nicNumber'])) {
                $this->response['message'] = 'NIC number is already registered';
                $this->response['errors']['nicNumber'] = 'This NIC number is already in use';
                return $this->response;
            }

            // Create user account
            $user_id = $this->createUserAccount($data, 'Customer');
            if (!$user_id) {
                throw new Exception('Failed to create user account');
            }

            // Handle file uploads (optional)
            $file_paths = $this->handleFileUploads($files, $user_id);

            // Create customer profile
            $customer_id = $this->createCustomerProfile($data, $user_id, $file_paths);
            if (!$customer_id) {
                throw new Exception('Failed to create customer profile');
            }

            // Commit transaction
            $conn->commit();

            // Start user session
            $this->startCustomerSession($user_id);

            // Prepare success response
            $this->response['success'] = true;
            $this->response['message'] = 'Registration successful! Welcome to SkyCamp.';
            $this->response['data'] = [
                'user_id' => $user_id,
                'customer_id' => $customer_id,
                'redirect_url' => '/profile'
            ];

            // Get complete user data for session
            $user_data = $this->getCustomerUserData($user_id);
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

            error_log("Customer registration error: " . $e->getMessage());
            $this->response['message'] = 'Registration failed. Please try again.';
            $this->response['errors']['general'] = $e->getMessage();

            return $this->response;
        }
    }

    /**
     * Validate customer registration data
     * 
     * @param array $data Form data
     * @return array Validation result
     */
    private function validateCustomerData($data)
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
            'gender'
        ];

        $errors = $this->validateBasicFields($data, $required_fields);

        // Customer-specific age validation (13+ years)
        if (isset($data['dob'])) {
            $age_error = $this->validateAge($data['dob'], 13);
            if ($age_error) {
                $errors['dob'] = $age_error;
            }
        }

        // Coordinates validation (optional)
        if (isset($data['latitude']) && !empty($data['latitude'])) {
            if (!is_numeric($data['latitude']) || $data['latitude'] < -90 || $data['latitude'] > 90) {
                $errors['latitude'] = 'Invalid latitude';
            }
        }
        if (isset($data['longitude']) && !empty($data['longitude'])) {
            if (!is_numeric($data['longitude']) || $data['longitude'] < -180 || $data['longitude'] > 180) {
                $errors['longitude'] = 'Invalid longitude';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create customer profile in database
     * 
     * @param array $data Form data
     * @param string $user_id User ID
     * @param array $file_paths Uploaded file paths
     * @return string|false Customer ID on success, false on failure
     */
    private function createCustomerProfile($data, $user_id, $file_paths)
    {
        $this->customer_model->setUserId($user_id);
        $this->customer_model->setFirstName($data['firstName']);
        $this->customer_model->setLastName($data['lastName']);
        $this->customer_model->setDob($data['dob']);
        $this->customer_model->setPhoneNumber($data['phoneNumber']);
        $this->customer_model->setHomeAddress($data['homeAddress']);
        $this->customer_model->setGender($data['gender']);
        $this->customer_model->setNicNumber($data['nicNumber']);

        // Set optional fields
        if (isset($data['travelBuddyStatus'])) {
            $this->customer_model->setTravelBuddyStatus($data['travelBuddyStatus']);
        }

        if (isset($data['latitude']) && !empty($data['latitude'])) {
            $this->customer_model->setLatitude($data['latitude']);
        }

        if (isset($data['longitude']) && !empty($data['longitude'])) {
            $this->customer_model->setLongitude($data['longitude']);
        }

        // Set file paths
        if ($file_paths['profile_picture']) {
            $this->customer_model->setProfilePicture($file_paths['profile_picture']);
        }

        if ($file_paths['nic_front_image']) {
            $this->customer_model->setNicImage($file_paths['nic_front_image']);
        }

        return $this->customer_model->create();
    }

    /**
     * Start customer session after successful registration
     * 
     * @param string $user_id User ID
     */
    private function startCustomerSession($user_id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_role'] = 'customer';
    }

    /**
     * Get complete customer user data for response
     * 
     * @param string $user_id User ID
     * @return array|null User data or null if not found
     */
    private function getCustomerUserData($user_id)
    {
        $customer_data = $this->customer_model->getCustomerByUserId($user_id);

        if ($customer_data) {
            return [
                'user_id' => $customer_data['user_id'],
                'customer_id' => $customer_data['customer_id'],
                'email' => $customer_data['email'],
                'first_name' => $customer_data['first_name'],
                'last_name' => $customer_data['last_name'],
                'full_name' => $customer_data['first_name'] . ' ' . $customer_data['last_name'],
                'user_role' => 'customer',
                'is_active' => $customer_data['is_active'],
                'travel_buddy_status' => $customer_data['travel_buddy_status'],
                'verification_status' => $customer_data['verification_status']
            ];
        }

        return null;
    }
}
