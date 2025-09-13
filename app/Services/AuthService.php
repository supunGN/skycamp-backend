<?php

/**
 * Authentication Service
 * Handles user registration, login, logout, and session management
 */

class AuthService
{
    private UserRepository $userRepository;
    private CustomerRepository $customerRepository;
    private RenterRepository $renterRepository;
    private GuideRepository $guideRepository;
    private Session $session;
    private FileService $fileService;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->customerRepository = new CustomerRepository();
        $this->renterRepository = new RenterRepository();
        $this->guideRepository = new GuideRepository();
        $this->session = new Session();
        $this->fileService = new FileService();
    }

    /**
     * Register a new user
     */
    public function register(Request $request): array
    {
        try {
            // Start database transaction
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Get form data
            $data = $request->getFormData();

            // Validate input data
            $errors = $this->validateRegistrationData($data, $request);
            if (!empty($errors)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            // Check if email already exists
            if ($this->userRepository->existsByEmail($data['email'])) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Email already in use'];
            }

            // Check if NIC already exists
            if ($this->nicExists($data['nicNumber'], $data['userRole'])) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'NIC number already in use'];
            }

            // Generate UUID for role-specific profile
            $roleId = Uuid::generate();

            // Create user
            $userData = [
                'email' => $data['email'],
                'password_hash' => Password::hash($data['password']),
                'role' => ucfirst($data['userRole']), // Capitalize first letter
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->userRepository->create($userData);

            // Handle file uploads using the new FileService method
            $profilePicturePath = null;
            $nicFrontImagePath = null;
            $nicBackImagePath = null;

            if ($request->file('profilePicture')) {
                $profilePicturePath = $this->fileService->saveUserImage($userId, $request->file('profilePicture'), 'profile');
            }

            if ($request->file('nicFrontImage')) {
                $nicFrontImagePath = $this->fileService->saveUserImage($userId, $request->file('nicFrontImage'), 'nic_front');
            }

            if ($request->file('nicBackImage')) {
                $nicBackImagePath = $this->fileService->saveUserImage($userId, $request->file('nicBackImage'), 'nic_back');
            }

            // Create role-specific profile
            $this->createRoleProfile($userId, $roleId, $data, $profilePicturePath, $nicFrontImagePath, $nicBackImagePath);

            // Commit transaction
            $pdo->commit();

            // Get the created user and role data
            $user = $this->userRepository->findById($userId);
            $dbUser = $user->toArray();

            // Get role-specific data
            $roleRow = null;
            switch ($data['userRole']) {
                case 'customer':
                    $customer = $this->customerRepository->findByUserId($userId);
                    $roleRow = $customer ? $customer->toArray() : null;
                    break;
                case 'renter':
                    $renter = $this->renterRepository->findByUserId($userId);
                    $roleRow = $renter ? $renter->toArray() : null;
                    break;
                case 'guide':
                    $guide = $this->guideRepository->findByUserId($userId);
                    $roleRow = $guide ? $guide->toArray() : null;
                    break;
            }

            // Start session with normalized data
            $this->session->setUser([
                'user_id' => $userId,
                'role' => $dbUser['role'],
                'provider_type' => $this->getProviderType($dbUser['role'])
            ]);
            $this->session->regenerate();

            // Return normalized user data
            $normalizedUser = $this->normalizeUserForClient($dbUser, $roleRow);
            $redirectUrl = $this->redirectForRole($dbUser['role']);

            return [
                'success' => true,
                'user' => $normalizedUser,
                'data' => [
                    'redirect_url' => $redirectUrl
                ]
            ];
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login(string $email, string $password): array
    {
        try {
            // Find user by email
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Check if user is active
            if (!$user->isActive) {
                return ['success' => false, 'message' => 'Account is deactivated'];
            }

            // Verify password
            if (!Password::verify($password, $user->passwordHash)) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Start session with normalized data
            $dbUser = $user->toArray();
            $this->session->setUser([
                'user_id' => $user->userId,
                'role' => $dbUser['role'],
                'provider_type' => $this->getProviderType($dbUser['role'])
            ]);
            $this->session->regenerate();

            // Get role-specific data
            $roleRow = null;
            switch ($dbUser['role']) {
                case 'Customer':
                    $customer = $this->customerRepository->findByUserId($user->userId);
                    $roleRow = $customer ? $customer->toArray() : null;
                    break;
                case 'Renter':
                    $renter = $this->renterRepository->findByUserId($user->userId);
                    $roleRow = $renter ? $renter->toArray() : null;
                    break;
                case 'Guide':
                    $guide = $this->guideRepository->findByUserId($user->userId);
                    $roleRow = $guide ? $guide->toArray() : null;
                    break;
            }

            // Return normalized user data
            $normalizedUser = $this->normalizeUserForClient($dbUser, $roleRow);
            $redirectUrl = $this->redirectForRole($dbUser['role']);

            return [
                'success' => true,
                'user' => $normalizedUser,
                'data' => [
                    'redirect_url' => $redirectUrl
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout(): array
    {
        try {
            $this->session->destroy();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Logout failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get current user
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->session->isAuthenticated()) {
            return null;
        }

        $userData = $this->session->getUser();
        $user = $this->userRepository->findById($userData['user_id']);

        if (!$user) {
            $this->session->destroy();
            return null;
        }

        $dbUser = $user->toArray();

        // Get role-specific data
        $roleRow = null;
        switch ($dbUser['role']) {
            case 'Customer':
                $customer = $this->customerRepository->findByUserId($user->userId);
                $roleRow = $customer ? $customer->toArray() : null;
                break;
            case 'Renter':
                $renter = $this->renterRepository->findByUserId($user->userId);
                $roleRow = $renter ? $renter->toArray() : null;
                break;
            case 'Guide':
                $guide = $this->guideRepository->findByUserId($user->userId);
                $roleRow = $guide ? $guide->toArray() : null;
                break;
        }

        return $this->normalizeUserForClient($dbUser, $roleRow);
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData(array $data, Request $request): array
    {
        $errors = [];

        // Common required fields
        $requiredFields = [
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
            'userRole'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace(['_', 'Password'], [' ', ' password'], $field)) . ' is required';
            }
        }

        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Password validation
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            } elseif (!preg_match('/[A-Z]/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one uppercase letter';
            } elseif (!preg_match('/[a-z]/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one lowercase letter';
            } elseif (!preg_match('/[0-9]/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one number';
            }
        }

        // Password confirmation
        if (
            !empty($data['password']) && !empty($data['confirmPassword']) &&
            $data['password'] !== $data['confirmPassword']
        ) {
            $errors['confirmPassword'] = 'Passwords do not match';
        }

        // Phone validation
        if (!empty($data['phoneNumber']) && !preg_match('/^0[1-9][0-9]{8}$/', $data['phoneNumber'])) {
            $errors['phoneNumber'] = 'Invalid phone number format';
        }

        // NIC validation
        if (!empty($data['nicNumber']) && !preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $data['nicNumber'])) {
            $errors['nicNumber'] = 'Invalid NIC number format';
        }

        // Gender validation
        if (!empty($data['gender']) && !in_array($data['gender'], ['Male', 'Female', 'Other'])) {
            $errors['gender'] = 'Invalid gender';
        }

        // Role validation
        if (!empty($data['userRole']) && !in_array($data['userRole'], ['customer', 'renter', 'guide'])) {
            $errors['userRole'] = 'Invalid user role';
        }

        // Role-specific validation
        if (!empty($data['userRole'])) {
            switch ($data['userRole']) {
                case 'renter':
                    if (empty($data['district'])) {
                        $errors['district'] = 'District is required for renters';
                    }
                    break;
                case 'guide':
                    if (empty($data['district'])) {
                        $errors['district'] = 'District is required for guides';
                    }
                    if (!empty($data['pricePerDay'])) {
                        if (!is_numeric($data['pricePerDay']) || $data['pricePerDay'] < 0) {
                            $errors['pricePerDay'] = 'Price per day must be a positive number';
                        }
                    }
                    break;
            }
        }

        // File validation
        if ($request->file('profilePicture')) {
            $file = $request->file('profilePicture');
            if (!$this->fileService->validateFileType($file)) {
                $errors['profilePicture'] = 'Invalid file type for profile picture';
            } elseif (!$this->fileService->validateFileSize($file)) {
                $errors['profilePicture'] = 'Profile picture file too large';
            }
        }

        if ($request->file('nicFrontImage')) {
            $file = $request->file('nicFrontImage');
            if (!$this->fileService->validateFileType($file)) {
                $errors['nicFrontImage'] = 'Invalid file type for NIC front image';
            } elseif (!$this->fileService->validateFileSize($file)) {
                $errors['nicFrontImage'] = 'NIC front image file too large';
            }
        }

        if ($request->file('nicBackImage')) {
            $file = $request->file('nicBackImage');
            if (!$this->fileService->validateFileType($file)) {
                $errors['nicBackImage'] = 'Invalid file type for NIC back image';
            } elseif (!$this->fileService->validateFileSize($file)) {
                $errors['nicBackImage'] = 'NIC back image file too large';
            }
        }

        return $errors;
    }

    /**
     * Check if NIC exists in any role table
     */
    private function nicExists(string $nicNumber, string $role): bool
    {
        switch ($role) {
            case 'customer':
                return $this->customerRepository->existsByNic($nicNumber);
            case 'renter':
                return $this->renterRepository->existsByNic($nicNumber);
            case 'guide':
                return $this->guideRepository->existsByNic($nicNumber);
            default:
                return false;
        }
    }

    /**
     * Create role-specific profile
     */
    private function createRoleProfile(string $userId, string $roleId, array $data, ?string $profilePicturePath, ?string $nicFrontImagePath, ?string $nicBackImagePath): void
    {
        $commonData = [
            'user_id' => $userId,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'dob' => $data['dob'],
            'phone_number' => $data['phoneNumber'],
            'home_address' => $data['homeAddress'],
            'gender' => $data['gender'],
            'profile_picture' => $profilePicturePath,
            'nic_number' => $data['nicNumber'],
            'nic_front_image' => $nicFrontImagePath,
            'nic_back_image' => $nicBackImagePath,
            'created_at' => date('Y-m-d H:i:s')
        ];

        switch ($data['userRole']) {
            case 'customer':
                $customerData = array_merge($commonData, [
                    'location' => $data['location'] ?? null,
                    'latitude' => !empty($data['latitude']) ? (float) $data['latitude'] : null,
                    'longitude' => !empty($data['longitude']) ? (float) $data['longitude'] : null,
                    'travel_buddy_status' => $data['travelBuddyStatus'] ?? 'Inactive',
                    'verification_status' => 'No'
                ]);
                $this->customerRepository->create($customerData);
                break;

            case 'renter':
                $renterData = array_merge($commonData, [
                    'camping_destinations' => $data['campingDestinations'] ?? null,
                    'stargazing_spots' => $data['stargazingSpots'] ?? null,
                    'district' => $data['district'] ?? null,
                    'verification_status' => 'No',
                    'latitude' => !empty($data['latitude']) ? (float) $data['latitude'] : null,
                    'longitude' => !empty($data['longitude']) ? (float) $data['longitude'] : null
                ]);
                $this->renterRepository->create($renterData);
                break;

            case 'guide':
                $guideData = array_merge($commonData, [
                    'camping_destinations' => $data['campingDestinations'] ?? null,
                    'stargazing_spots' => $data['stargazingSpots'] ?? null,
                    'district' => $data['district'] ?? null,
                    'description' => $data['description'] ?? null,
                    'special_note' => $data['specialNote'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'languages' => $data['languages'] ?? null,
                    'price_per_day' => !empty($data['pricePerDay']) ? (float) $data['pricePerDay'] : null,
                    'verification_status' => 'No'
                ]);
                $this->guideRepository->create($guideData);
                break;
        }
    }

    /**
     * Get provider type based on database role
     */
    private function getProviderType(string $dbRole): ?string
    {
        switch ($dbRole) {
            case 'Renter':
                return 'Equipment Renter';
            case 'Guide':
                return 'Local Guide';
            default:
                return null;
        }
    }

    /**
     * Normalize user data for client response
     */
    public function normalizeUserForClient(array $dbUser, ?array $roleRow): array
    {
        $userRole = $dbUser['role'];
        $providerType = null;

        // Map database role to frontend user_role and provider_type
        switch ($userRole) {
            case 'Customer':
                $userRole = 'customer';
                $providerType = null;
                break;
            case 'Renter':
                $userRole = 'service_provider';
                $providerType = 'Equipment Renter';
                break;
            case 'Guide':
                $userRole = 'service_provider';
                $providerType = 'Local Guide';
                break;
        }

        // Build normalized user object
        $normalizedUser = [
            'id' => $dbUser['user_id'],
            'email' => $dbUser['email'],
            'user_role' => $userRole,
            'provider_type' => $providerType
        ];

        // Add role-specific data if available
        if ($roleRow) {
            $normalizedUser = array_merge($normalizedUser, [
                'first_name' => $roleRow['first_name'] ?? '',
                'last_name' => $roleRow['last_name'] ?? '',
                'phone_number' => $roleRow['phone_number'] ?? '',
                'dob' => $roleRow['dob'] ?? '',
                'gender' => $roleRow['gender'] ?? '',
                'home_address' => $roleRow['home_address'] ?? '',
                'profile_picture' => $roleRow['profile_picture'] ?? null
            ]);
        }

        return $normalizedUser;
    }

    /**
     * Get redirect URL based on user role
     */
    public function redirectForRole(string $dbRole): string
    {
        switch ($dbRole) {
            case 'Customer':
                return '/profile';
            case 'Renter':
                return '/dashboard/renter/overview';
            case 'Guide':
                return '/dashboard/guide/overview';
            default:
                return '/profile';
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): array
    {
        try {
            // Check if user is authenticated
            if (!$this->session->isAuthenticated()) {
                return ['success' => false, 'message' => 'Not authenticated'];
            }

            $userData = $this->session->getUser();
            $user = $this->userRepository->findById($userData['user_id']);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Get form data (FormData is sent as POST data)
            $data = $request->getFormData();

            // Validate required fields
            $errors = $this->validateProfileUpdateData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }

            // Handle profile picture upload
            $profilePicturePath = null;
            if ($request->file('profilePicture')) {
                $profilePicturePath = $this->fileService->saveUserImage($user->userId, $request->file('profilePicture'), 'profile');
                if (!$profilePicturePath) {
                    return ['success' => false, 'message' => 'Failed to upload profile picture'];
                }
            }

            // Update role-specific profile
            $updateResult = $this->updateRoleProfile($user->userId, $data, $profilePicturePath);

            if (!$updateResult['success']) {
                return $updateResult;
            }

            // Get updated user data
            $updatedUser = $this->getCurrentUser();

            return [
                'success' => true,
                'user' => $updatedUser,
                'message' => 'Profile updated successfully'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Profile update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Validate profile update data
     */
    private function validateProfileUpdateData(array $data): array
    {
        $errors = [];

        if (empty($data['firstName'])) {
            $errors['firstName'] = 'First name is required';
        }

        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Last name is required';
        }

        if (empty($data['phoneNumber'])) {
            $errors['phoneNumber'] = 'Phone number is required';
        } elseif (!preg_match('/^0[1-9][0-9]{8}$/', trim($data['phoneNumber']))) {
            $errors['phoneNumber'] = 'Invalid phone number format: ' . trim($data['phoneNumber']) . ' (expected format: 0771234567)';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!empty($data['gender']) && !in_array($data['gender'], ['Male', 'Female', 'Other'])) {
            $errors['gender'] = 'Invalid gender';
        }

        return $errors;
    }

    /**
     * Update role-specific profile
     */
    private function updateRoleProfile(string $userId, array $data, ?string $profilePicturePath): array
    {
        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            switch ($user->role) {
                case 'Customer':
                    $customer = $this->customerRepository->findByUserId($userId);
                    if ($customer) {
                        $updateData = [
                            'customer_id' => $customer->customerId,
                            'first_name' => $data['firstName'],
                            'last_name' => $data['lastName'],
                            'phone_number' => $data['phoneNumber'],
                            'home_address' => $data['homeAddress'] ?? '',
                            'gender' => $data['gender'] ?? 'Male',
                            'dob' => $data['dob'] ?? $customer->dob,
                            'location' => $customer->location ?? '',
                            'latitude' => $customer->latitude ?? null,
                            'longitude' => $customer->longitude ?? null,
                            'nic_number' => $customer->nicNumber ?? '',
                            'nic_front_image' => $customer->nicImage ?? '',
                            'nic_back_image' => $customer->nicImage ?? '',
                            'travel_buddy_status' => $customer->travelBuddyStatus ?? 'Inactive',
                            'verification_status' => $customer->verificationStatus ?? 'No'
                        ];

                        // Add profile picture (use existing if not provided)
                        $updateData['profile_picture'] = $profilePicturePath ?? $customer->profilePicture;

                        // Debug logging
                        error_log("Customer update data: " . json_encode($updateData));

                        $result = $this->customerRepository->update($updateData);
                        if (!$result) {
                            error_log("Failed to update customer profile for user: " . $userId);
                            return ['success' => false, 'message' => 'Failed to update profile in database'];
                        }
                    }
                    break;

                case 'Renter':
                    $renter = $this->renterRepository->findByUserId($userId);
                    if ($renter) {
                        $updateData = [
                            'renter_id' => $renter->renterId,
                            'first_name' => $data['firstName'],
                            'last_name' => $data['lastName'],
                            'phone_number' => $data['phoneNumber'],
                            'home_address' => $data['homeAddress'] ?? '',
                            'gender' => $data['gender'] ?? 'Male',
                            'dob' => $data['dob'] ?? $renter->dob,
                            'camping_destinations' => $renter->campingDestinations ?? '',
                            'stargazing_spots' => $renter->stargazingSpots ?? '',
                            'district' => $renter->district ?? '',
                            'verification_status' => $renter->verificationStatus ?? 'No',
                            'latitude' => $renter->latitude ?? null,
                            'longitude' => $renter->longitude ?? null,
                            'nic_number' => $renter->nicNumber ?? '',
                            'nic_front_image' => $renter->nicImage ?? '',
                            'nic_back_image' => $renter->nicImage ?? ''
                        ];

                        // Add profile picture (use existing if not provided)
                        $updateData['profile_picture'] = $profilePicturePath ?? $renter->profilePicture;

                        $result = $this->renterRepository->update($updateData);
                        if (!$result) {
                            error_log("Failed to update renter profile for user: " . $userId);
                            return ['success' => false, 'message' => 'Failed to update profile in database'];
                        }
                    }
                    break;

                case 'Guide':
                    $guide = $this->guideRepository->findByUserId($userId);
                    if ($guide) {
                        $updateData = [
                            'guide_id' => $guide->guideId,
                            'first_name' => $data['firstName'],
                            'last_name' => $data['lastName'],
                            'phone_number' => $data['phoneNumber'],
                            'home_address' => $data['homeAddress'] ?? '',
                            'gender' => $data['gender'] ?? 'Male',
                            'dob' => $data['dob'] ?? $guide->dob,
                            'camping_destinations' => $guide->campingDestinations ?? '',
                            'stargazing_spots' => $guide->stargazingSpots ?? '',
                            'district' => $guide->district ?? '',
                            'verification_status' => $guide->verificationStatus ?? 'No',
                            'latitude' => $guide->latitude ?? null,
                            'longitude' => $guide->longitude ?? null,
                            'nic_number' => $guide->nicNumber ?? '',
                            'nic_front_image' => $guide->nicImage ?? '',
                            'nic_back_image' => $guide->nicImage ?? ''
                        ];

                        // Add profile picture (use existing if not provided)
                        $updateData['profile_picture'] = $profilePicturePath ?? $guide->profilePicture;

                        $result = $this->guideRepository->update($updateData);
                        if (!$result) {
                            error_log("Failed to update guide profile for user: " . $userId);
                            return ['success' => false, 'message' => 'Failed to update profile in database'];
                        }
                    }
                    break;

                default:
                    return ['success' => false, 'message' => 'Invalid user role'];
            }

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }
}
