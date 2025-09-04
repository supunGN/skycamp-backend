<?php

/**
 * Validation Service Class
 * Centralized validation logic for form data
 * Following data validation principles from Lecture 2 and security from Lecture 6
 */

class ValidationService
{
    private $errors = [];

    /**
     * Validate user registration data
     * 
     * @param array $data Form data to validate
     * @param string $role User role for role-specific validation
     * @return array Validation result
     */
    public function validateRegistrationData($data, $role = 'Customer')
    {
        $this->errors = [];

        // Basic field validation
        $this->validateRequired($data, ['email', 'password', 'firstName', 'lastName', 'dob', 'gender']);

        // Email validation
        $this->validateEmail($data['email'] ?? '');

        // Password validation
        $this->validatePassword($data['password'] ?? '', $data['confirmPassword'] ?? '');

        // Name validation
        $this->validateName($data['firstName'] ?? '', 'firstName');
        $this->validateName($data['lastName'] ?? '', 'lastName');

        // Date of birth validation
        $this->validateDateOfBirth($data['dob'] ?? '', $role);

        // Gender validation
        $this->validateGender($data['gender'] ?? '');

        // Phone number validation
        if (!empty($data['phoneNumber'])) {
            $this->validatePhoneNumber($data['phoneNumber']);
        }

        // NIC validation (for Renter and Guide)
        if (in_array($role, ['Renter', 'Guide']) && !empty($data['nicNumber'])) {
            $this->validateNIC($data['nicNumber']);
        }

        // Role-specific validation
        $this->validateRoleSpecificData($data, $role);

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }

    /**
     * Validate login data
     * 
     * @param array $data Login form data
     * @return array Validation result
     */
    public function validateLoginData($data)
    {
        $this->errors = [];

        // Required fields
        $this->validateRequired($data, ['email', 'password']);

        // Email format
        if (!empty($data['email'])) {
            $this->validateEmail($data['email']);
        }

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }

    /**
     * Validate profile update data
     * 
     * @param array $data Profile data
     * @param string $role User role
     * @return array Validation result
     */
    public function validateProfileData($data, $role)
    {
        $this->errors = [];

        // Optional field validation
        if (isset($data['firstName'])) {
            $this->validateName($data['firstName'], 'firstName');
        }

        if (isset($data['lastName'])) {
            $this->validateName($data['lastName'], 'lastName');
        }

        if (isset($data['phoneNumber'])) {
            $this->validatePhoneNumber($data['phoneNumber']);
        }

        if (isset($data['dob'])) {
            $this->validateDateOfBirth($data['dob'], $role);
        }

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }

    /**
     * Validate required fields
     */
    private function validateRequired($data, $fields)
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $this->errors[$field] = $this->getFieldLabel($field) . ' is required';
            }
        }
    }

    /**
     * Validate email format and security
     */
    private function validateEmail($email)
    {
        if (empty($email)) {
            return;
        }

        // Check format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
            return;
        }

        // Check length
        if (strlen($email) > 150) {
            $this->errors['email'] = 'Email address is too long';
            return;
        }

        // Check for suspicious patterns
        if (preg_match('/[<>"\']/', $email)) {
            $this->errors['email'] = 'Email contains invalid characters';
            return;
        }

        // Domain validation
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            $this->errors['email'] = 'Email domain is not valid';
        }
    }

    /**
     * Validate password strength and confirmation
     */
    private function validatePassword($password, $confirmPassword = '')
    {
        if (empty($password)) {
            return;
        }

        // Length check
        if (strlen($password) < 8) {
            $this->errors['password'] = 'Password must be at least 8 characters long';
            return;
        }

        if (strlen($password) > 100) {
            $this->errors['password'] = 'Password is too long';
            return;
        }

        // Complexity check
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $this->errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
            return;
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password',
            'password123',
            '12345678',
            'qwerty123',
            'admin123',
            'welcome123',
            'letmein123'
        ];

        if (in_array(strtolower($password), $weakPasswords)) {
            $this->errors['password'] = 'Password is too common. Please choose a stronger password';
            return;
        }

        // Confirmation check
        if (!empty($confirmPassword) && $password !== $confirmPassword) {
            $this->errors['confirmPassword'] = 'Passwords do not match';
        }
    }

    /**
     * Validate name fields
     */
    private function validateName($name, $field)
    {
        if (empty($name)) {
            return;
        }

        // Length check
        if (strlen($name) < 2) {
            $this->errors[$field] = $this->getFieldLabel($field) . ' must be at least 2 characters long';
            return;
        }

        if (strlen($name) > 100) {
            $this->errors[$field] = $this->getFieldLabel($field) . ' is too long';
            return;
        }

        // Character check - only letters, spaces, hyphens, apostrophes
        if (!preg_match("/^[a-zA-Z\s\-'\.]+$/", $name)) {
            $this->errors[$field] = $this->getFieldLabel($field) . ' can only contain letters, spaces, hyphens, and apostrophes';
        }
    }

    /**
     * Validate date of birth with age requirements
     */
    private function validateDateOfBirth($dob, $role)
    {
        if (empty($dob)) {
            return;
        }

        // Format validation
        $date = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$date || $date->format('Y-m-d') !== $dob) {
            $this->errors['dob'] = 'Invalid date format. Please use YYYY-MM-DD';
            return;
        }

        // Future date check
        if ($date > new DateTime()) {
            $this->errors['dob'] = 'Date of birth cannot be in the future';
            return;
        }

        // Maximum age check (150 years)
        $maxDate = new DateTime('-150 years');
        if ($date < $maxDate) {
            $this->errors['dob'] = 'Invalid date of birth';
            return;
        }

        // Age requirements based on role
        $ageRequirements = [
            'Customer' => 13,
            'Renter' => 18,
            'Guide' => 18
        ];

        $minimumAge = $ageRequirements[$role] ?? 13;
        $minDate = new DateTime("-{$minimumAge} years");

        if ($date > $minDate) {
            $this->errors['dob'] = "Must be at least {$minimumAge} years old for {$role} role";
        }
    }

    /**
     * Validate gender selection
     */
    private function validateGender($gender)
    {
        if (empty($gender)) {
            return;
        }

        $validGenders = ['Male', 'Female', 'Other'];
        if (!in_array($gender, $validGenders)) {
            $this->errors['gender'] = 'Invalid gender selection';
        }
    }

    /**
     * Validate Sri Lankan phone number
     */
    private function validatePhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Sri Lankan mobile number validation
        if (!preg_match('/^0[1-9][0-9]{8}$/', $phone)) {
            $this->errors['phoneNumber'] = 'Invalid Sri Lankan phone number format (0XXXXXXXXX)';
        }
    }

    /**
     * Validate Sri Lankan NIC number
     */
    private function validateNIC($nicNumber)
    {
        if (empty($nicNumber)) {
            return;
        }

        $nic = strtoupper(trim($nicNumber));

        // Old format: 9 digits + V/X
        // New format: 12 digits
        if (!preg_match('/^[0-9]{9}[VX]$/', $nic) && !preg_match('/^[0-9]{12}$/', $nic)) {
            $this->errors['nicNumber'] = 'Invalid NIC format. Use either XXXXXXXXXV/X or XXXXXXXXXXXX';
        }
    }

    /**
     * Validate role-specific data
     */
    private function validateRoleSpecificData($data, $role)
    {
        switch ($role) {
            case 'Renter':
                $this->validateRenterData($data);
                break;
            case 'Guide':
                $this->validateGuideData($data);
                break;
        }
    }

    /**
     * Validate renter-specific data
     */
    private function validateRenterData($data)
    {
        // District validation
        if (!empty($data['district'])) {
            $validDistricts = [
                'Colombo',
                'Gampaha',
                'Kalutara',
                'Kandy',
                'Matale',
                'Nuwara Eliya',
                'Galle',
                'Matara',
                'Hambantota',
                'Jaffna',
                'Kilinochchi',
                'Mannar',
                'Vavuniya',
                'Mullaitivu',
                'Batticaloa',
                'Ampara',
                'Trincomalee',
                'Kurunegala',
                'Puttalam',
                'Anuradhapura',
                'Polonnaruwa',
                'Badulla',
                'Moneragala',
                'Ratnapura',
                'Kegalle'
            ];

            if (!in_array($data['district'], $validDistricts)) {
                $this->errors['district'] = 'Invalid district selection';
            }
        }

        // Service areas validation
        if (!empty($data['campingDestinations'])) {
            $this->validateServiceAreas($data['campingDestinations'], 'campingDestinations');
        }

        if (!empty($data['stargazingSpots'])) {
            $this->validateServiceAreas($data['stargazingSpots'], 'stargazingSpots');
        }
    }

    /**
     * Validate guide-specific data
     */
    private function validateGuideData($data)
    {
        // Price validation
        if (isset($data['pricePerDay'])) {
            if (!is_numeric($data['pricePerDay']) || $data['pricePerDay'] < 0) {
                $this->errors['pricePerDay'] = 'Price must be a valid positive number';
            } elseif ($data['pricePerDay'] > 50000) {
                $this->errors['pricePerDay'] = 'Price seems unreasonably high';
            }
        }

        // Description validation
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $this->errors['description'] = 'Description is too long (max 1000 characters)';
        }

        // Languages validation
        if (!empty($data['languages'])) {
            $validLanguages = ['Sinhala', 'Tamil', 'English'];
            $languages = explode(',', $data['languages']);

            foreach ($languages as $lang) {
                $lang = trim($lang);
                if (!in_array($lang, $validLanguages)) {
                    $this->errors['languages'] = 'Invalid language selection. Choose from: ' . implode(', ', $validLanguages);
                    break;
                }
            }
        }

        // Use renter validation for common fields
        $this->validateRenterData($data);
    }

    /**
     * Validate service areas (camping destinations/stargazing spots)
     */
    private function validateServiceAreas($areas, $field)
    {
        if (is_string($areas)) {
            $areaList = explode(',', $areas);

            if (count($areaList) > 10) {
                $this->errors[$field] = 'Too many areas selected (maximum 10)';
            }

            foreach ($areaList as $area) {
                $area = trim($area);
                if (strlen($area) > 100) {
                    $this->errors[$field] = 'Area name is too long';
                    break;
                }
            }
        }
    }

    /**
     * Get human-readable field labels
     */
    private function getFieldLabel($field)
    {
        $labels = [
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'dob' => 'Date of Birth',
            'phoneNumber' => 'Phone Number',
            'nicNumber' => 'NIC Number',
            'homeAddress' => 'Home Address',
            'campingDestinations' => 'Camping Destinations',
            'stargazingSpots' => 'Stargazing Spots',
            'pricePerDay' => 'Price per Day',
            'confirmPassword' => 'Confirm Password'
        ];

        return $labels[$field] ?? ucfirst($field);
    }

    /**
     * Validate file uploads
     */
    public function validateFileUpload($file, $type = 'image')
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => true, 'errors' => []]; // Optional files
        }

        $errors = [];

        // File size validation (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size must be less than 5MB';
        }

        // File type validation
        if ($type === 'image') {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'Only JPEG, PNG, and WebP images are allowed';
            }
        }

        // File name validation
        $fileName = $file['name'];
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $fileName)) {
            $errors[] = 'File name contains invalid characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
