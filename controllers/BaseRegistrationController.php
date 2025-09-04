<?php

/**
 * Base Registration Controller Class
 * Contains shared functionality for all registration controllers
 * Following DRY principle and inheritance
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/FileUpload.php';

abstract class BaseRegistrationController
{
    protected $user_model;
    protected $file_upload;
    protected $response;

    /**
     * Constructor - Initialize shared models and utilities
     */
    public function __construct()
    {
        $this->user_model = new User();
        $this->file_upload = new FileUpload('../uploads/');

        // Initialize response structure
        $this->response = [
            'success' => false,
            'message' => '',
            'data' => null,
            'errors' => []
        ];
    }

    /**
     * Abstract method for role-specific registration
     * Must be implemented by child classes
     */
    abstract public function register($data, $files = []);

    /**
     * Shared validation for basic user fields
     * 
     * @param array $data Form data
     * @param array $required_fields List of required fields
     * @return array Validation result
     */
    protected function validateBasicFields($data, $required_fields)
    {
        $errors = [];

        // Check required fields
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        // Email validation
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
            if (strlen($data['email']) > 150) {
                $errors['email'] = 'Email is too long';
            }
        }

        // Password validation
        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long';
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $data['password'])) {
                $errors['password'] = 'Password must contain uppercase, lowercase, and number';
            }
        }

        // Confirm password validation
        if (isset($data['password']) && isset($data['confirmPassword'])) {
            if ($data['password'] !== $data['confirmPassword']) {
                $errors['confirmPassword'] = 'Passwords do not match';
            }
        }

        // Name validation
        if (isset($data['firstName']) && strlen($data['firstName']) > 100) {
            $errors['firstName'] = 'First name is too long';
        }
        if (isset($data['lastName']) && strlen($data['lastName']) > 100) {
            $errors['lastName'] = 'Last name is too long';
        }

        // Date of birth validation
        if (isset($data['dob'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['dob']);
            if (!$date || $date->format('Y-m-d') !== $data['dob']) {
                $errors['dob'] = 'Invalid date format';
            }
        }

        // Phone number validation (Sri Lankan format)
        if (isset($data['phoneNumber'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['phoneNumber']);
            if (!preg_match('/^0[1-9][0-9]{8}$/', $phone)) {
                $errors['phoneNumber'] = 'Invalid Sri Lankan phone number format';
            }
        }

        // NIC validation (Sri Lankan format)
        if (isset($data['nicNumber'])) {
            $nic = strtoupper(trim($data['nicNumber']));
            if (!preg_match('/^[0-9]{9}[VX]$/', $nic)) {
                $errors['nicNumber'] = 'Invalid NIC format (9 digits + V/X)';
            }
        }

        // Gender validation
        if (isset($data['gender'])) {
            $valid_genders = ['Male', 'Female', 'Other'];
            if (!in_array($data['gender'], $valid_genders)) {
                $errors['gender'] = 'Invalid gender selection';
            }
        }

        return $errors;
    }

    /**
     * Validate age requirement
     * 
     * @param string $dob Date of birth
     * @param int $min_age Minimum age required
     * @return string|null Error message or null if valid
     */
    protected function validateAge($dob, $min_age = 13)
    {
        $date = DateTime::createFromFormat('Y-m-d', $dob);
        if ($date) {
            $min_date = new DateTime("-{$min_age} years");
            if ($date > $min_date) {
                return "Must be at least {$min_age} years old";
            }
        }
        return null;
    }

    /**
     * Create user account in database
     * 
     * @param array $data Form data
     * @param string $role User role
     * @return string|false User ID on success, false on failure
     */
    protected function createUserAccount($data, $role)
    {
        $this->user_model->setEmail($data['email']);
        $this->user_model->setPasswordHash($data['password']);
        $this->user_model->setRole($role);
        $this->user_model->setIsActive(true);

        return $this->user_model->create();
    }

    /**
     * Handle file uploads securely
     * 
     * @param array $files $_FILES array
     * @param string $user_id User ID
     * @return array File paths
     */
    protected function handleFileUploads($files, $user_id)
    {
        $file_paths = [
            'profile_picture' => null,
            'nic_front_image' => null,
            'nic_back_image' => null
        ];

        // Handle profile picture upload (optional)
        if (isset($files['profilePicture']) && $files['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $profile_path = $this->file_upload->uploadProfilePicture($files['profilePicture'], $user_id);
            if ($profile_path) {
                $file_paths['profile_picture'] = $profile_path;
            }
        }

        // Handle NIC front image upload (optional)
        if (isset($files['nicFrontImage']) && $files['nicFrontImage']['error'] === UPLOAD_ERR_OK) {
            $nic_front_path = $this->file_upload->uploadNicImage($files['nicFrontImage'], $user_id, 'front');
            if ($nic_front_path) {
                $file_paths['nic_front_image'] = $nic_front_path;
            }
        }

        // Handle NIC back image upload (optional)
        if (isset($files['nicBackImage']) && $files['nicBackImage']['error'] === UPLOAD_ERR_OK) {
            $nic_back_path = $this->file_upload->uploadNicImage($files['nicBackImage'], $user_id, 'back');
            if ($nic_back_path) {
                $file_paths['nic_back_image'] = $nic_back_path;
            }
        }

        return $file_paths;
    }

    /**
     * Clean up uploaded files on error
     * 
     * @param array $file_paths File paths to clean up
     */
    protected function cleanupUploadedFiles($file_paths)
    {
        foreach ($file_paths as $path) {
            if ($path && file_exists($path)) {
                $this->file_upload->deleteFile($path);
            }
        }
    }

    /**
     * Get upload limits for frontend validation
     * 
     * @return array Upload limits
     */
    public function getUploadLimits()
    {
        return $this->file_upload->getUploadLimits();
    }
}
