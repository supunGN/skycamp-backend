<?php

/**
 * Guide Dashboard Controller
 * Handles guide dashboard-related API endpoints
 */

use App\Services\NotificationService;

class GuideDashboardController extends Controller
{
    private PDO $pdo;
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getConnection();
        $this->notificationService = new NotificationService($this->pdo);
    }

    /**
     * Get guide dashboard statistics
     * GET /api/guide/dashboard/stats
     */
    public function getDashboardStats(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get dashboard statistics
            $stats = $this->calculateDashboardStats($guideId);

            $response->json([
                'success' => true,
                'data' => $stats
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching guide dashboard stats: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics'
            ], 500);
        }
    }

    /**
     * Get guide ID by user ID
     */
    private function getGuideIdByUserId(int $userId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT guide_id FROM guides WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['guide_id'] : null;
    }

    /**
     * Calculate dashboard statistics for a guide
     */
    private function calculateDashboardStats(int $guideId): array
    {
        // 1. Finished Trips (Completed bookings where this guide provided service)
        $finishedTrips = $this->getFinishedTrips($guideId);

        // 2. Pending Bookings (Confirmed bookings that haven't ended yet)
        $pendingBookings = $this->getPendingBookings($guideId);

        // 3. Received Reviews (Total reviews received for this guide)
        $receivedReviews = $this->getReceivedReviews($guideId);

        return [
            'finishedTrips' => $finishedTrips,
            'pendingBookings' => $pendingBookings,
            'receivedReviews' => $receivedReviews
        ];
    }

    /**
     * Get total finished trips (completed bookings)
     */
    private function getFinishedTrips(int $guideId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE guide_id = ? 
            AND booking_type = 'Guide' 
            AND status = 'Completed'
        ");
        $stmt->execute([$guideId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get total pending bookings (confirmed bookings that haven't ended yet)
     */
    private function getPendingBookings(int $guideId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE guide_id = ? 
            AND booking_type = 'Guide' 
            AND status = 'Confirmed' 
            AND end_date >= CURDATE()
        ");
        $stmt->execute([$guideId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get total reviews received for this guide
     */
    private function getReceivedReviews(int $guideId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reviews 
            WHERE entity_type = 'Guide' 
            AND entity_id = ? 
            AND status = 'Active'
        ");
        $stmt->execute([$guideId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get guide profile details
     * GET /api/guide/profile
     */
    public function getProfile(Request $request, Response $response): void
    {
        try {
            error_log("GuideDashboardController::getProfile - Method called");
            // Get current user from session
            $userId = $this->session->get('user_id');
            error_log("GuideDashboardController::getProfile - User ID from session: " . ($userId ?? 'null'));

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide profile with user data
            error_log("GuideDashboardController::getProfile - Fetching profile for user ID: " . $userId);
            $stmt = $this->pdo->prepare("
                SELECT 
                    g.*,
                    u.email,
                    u.created_at as user_created_at
                FROM guides g
                JOIN users u ON g.user_id = u.user_id
                WHERE g.user_id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("GuideDashboardController::getProfile - Profile data: " . json_encode($profile));

            if (!$profile) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            $response->json([
                'success' => true,
                'data' => $profile
            ]);
        } catch (Exception $e) {
            error_log("GuideDashboardController::getProfile - Error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }

    /**
     * Update guide profile
     * POST /api/guide/profile
     */
    public function updateProfile(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get form data (FormData is sent as POST data)
            $data = $request->getFormData();

            // Validate required fields
            $errors = [];
            $firstName = $data['firstName'] ?? '';
            $lastName = $data['lastName'] ?? '';
            $phoneNumber = $data['phoneNumber'] ?? '';
            $homeAddress = $data['homeAddress'] ?? '';
            $gender = $data['gender'] ?? '';
            $dob = $data['dob'] ?? '';
            $description = $data['description'] ?? '';
            $specialNote = $data['specialNote'] ?? '';
            $languages = $data['languages'] ?? '';
            $pricePerDay = $data['pricePerDay'] ?? '';
            $district = $data['district'] ?? '';
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;

            if (empty($firstName)) {
                $errors['firstName'] = 'First name is required';
            }
            if (empty($lastName)) {
                $errors['lastName'] = 'Last name is required';
            }
            if (empty($phoneNumber)) {
                $errors['phoneNumber'] = 'Phone number is required';
            } elseif (!preg_match('/^0[1-9][0-9]{8}$/', $phoneNumber)) {
                $errors['phoneNumber'] = 'Please enter a valid Sri Lankan phone number (0xxxxxxxxx)';
            }
            if (empty($homeAddress)) {
                $errors['homeAddress'] = 'Home address is required';
            }
            if (empty($gender)) {
                $errors['gender'] = 'Gender is required';
            } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
                $errors['gender'] = 'Invalid gender selection';
            }
            if (empty($district)) {
                $errors['district'] = 'District is required';
            }
            if (empty($languages)) {
                $errors['languages'] = 'Languages are required';
            }
            if (empty($pricePerDay)) {
                $errors['pricePerDay'] = 'Price per day is required';
            } elseif (!is_numeric($pricePerDay) || $pricePerDay <= 0) {
                $errors['pricePerDay'] = 'Price per day must be a positive number';
            }

            // Validate coordinates if provided
            if ($latitude !== null && $longitude !== null) {
                if (!is_numeric($latitude) || !is_numeric($longitude)) {
                    $errors['location'] = 'Invalid coordinates format';
                } elseif ($latitude < 5.916 || $latitude > 9.835 || $longitude < 79.652 || $longitude > 81.881) {
                    $errors['location'] = 'Location must be within Sri Lanka';
                }
            }

            if (!empty($errors)) {
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Handle profile picture upload
            $profilePicturePath = null;
            if ($request->file('profilePicture')) {
                $fileService = new FileService();
                $profilePicturePath = $fileService->saveUserImage($userId, $request->file('profilePicture'), 'profile');
                if (!$profilePicturePath) {
                    $errors['profilePicture'] = 'Failed to upload profile picture. Please try again.';
                }
            }

            if (!empty($errors)) {
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Update guide profile
            $updateFields = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $phoneNumber,
                'home_address' => $homeAddress,
                'gender' => $gender,
                'dob' => $dob ?: null,
                'description' => $description,
                'special_note' => $specialNote,
                'languages' => $languages,
                'price_per_day' => $pricePerDay,
                'district' => $district,
                'latitude' => $latitude ?: null,
                'longitude' => $longitude ?: null
            ];

            if ($profilePicturePath) {
                $updateFields['profile_picture'] = $profilePicturePath;
            }

            $setClause = implode(', ', array_map(fn($field) => "$field = ?", array_keys($updateFields)));
            $values = array_values($updateFields);
            $values[] = $guideId;

            $stmt = $this->pdo->prepare("UPDATE guides SET $setClause WHERE guide_id = ?");
            $stmt->execute($values);

            // Get updated profile
            $stmt = $this->pdo->prepare("
                SELECT 
                    g.*,
                    u.email,
                    u.created_at as user_created_at
                FROM guides g
                JOIN users u ON g.user_id = u.user_id
                WHERE g.guide_id = ?
            ");
            $stmt->execute([$guideId]);
            $updatedProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedProfile
            ]);
        } catch (Exception $e) {
            error_log("GuideDashboardController::updateProfile - Error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    /**
     * Get verification documents for the logged-in guide
     */
    public function getVerificationDocs(Request $request, Response $response)
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    verification_status,
                    nic_front_image,
                    nic_back_image
                FROM guides 
                WHERE guide_id = ?
            ");
            $stmt->execute([$guideId]);
            $guide = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$guide) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide not found'
                ], 404);
                return;
            }

            // Build image URLs
            $nicFrontImageUrl = null;
            $nicBackImageUrl = null;

            if ($guide['nic_front_image']) {
                $nicFrontImageUrl = $this->buildImageUrl($guide['nic_front_image']);
            }

            if ($guide['nic_back_image']) {
                $nicBackImageUrl = $this->buildImageUrl($guide['nic_back_image']);
            }

            $response->json([
                'success' => true,
                'data' => [
                    'verification_status' => $guide['verification_status'],
                    'nic_front_image_url' => $nicFrontImageUrl,
                    'nic_back_image_url' => $nicBackImageUrl,
                    'rejection_reason' => null,
                    'rejection_date' => null,
                    'can_resubmit' => true
                ]
            ]);
        } catch (Exception $e) {
            error_log("Get verification docs error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch verification documents'
            ], 500);
        }
    }

    /**
     * Submit verification documents for admin review
     */
    public function submitVerification(Request $request, Response $response)
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            $data = $request->getFormData();
            $nicFrontImage = $request->file('nic_front_image');
            $nicBackImage = $request->file('nic_back_image');

            // Check if we have new files or existing images
            $hasNewFiles = $nicFrontImage && $nicBackImage;
            $hasExistingImages = false;

            if (!$hasNewFiles) {
                // Check if guide already has NIC images
                $stmt = $this->pdo->prepare("
                    SELECT nic_front_image, nic_back_image 
                    FROM guides 
                    WHERE guide_id = ?
                ");
                $stmt->execute([$guideId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing && $existing['nic_front_image'] && $existing['nic_back_image']) {
                    $hasExistingImages = true;
                } else {
                    $response->json([
                        'success' => false,
                        'message' => 'Both NIC front and back images are required'
                    ], 400);
                    return;
                }
            }

            // Handle file uploads if new files are provided
            if ($hasNewFiles) {
                $fileService = new FileService();

                // Upload NIC front image
                $nicFrontPath = $fileService->saveUserImage($userId, $nicFrontImage, 'nic_front');
                if (!$nicFrontPath) {
                    $response->json([
                        'success' => false,
                        'message' => 'Failed to upload NIC front image'
                    ], 400);
                    return;
                }

                // Upload NIC back image
                $nicBackPath = $fileService->saveUserImage($userId, $nicBackImage, 'nic_back');
                if (!$nicBackPath) {
                    $response->json([
                        'success' => false,
                        'message' => 'Failed to upload NIC back image'
                    ], 400);
                    return;
                }

                // Update guide record with new images
                $stmt = $this->pdo->prepare("
                    UPDATE guides 
                    SET 
                        nic_front_image = ?,
                        nic_back_image = ?,
                        verification_status = 'Pending'
                    WHERE guide_id = ?
                ");
                $stmt->execute([$nicFrontPath, $nicBackPath, $guideId]);
            } else {
                // Submit existing images for verification
                $stmt = $this->pdo->prepare("
                    UPDATE guides 
                    SET 
                        verification_status = 'Pending'
                    WHERE guide_id = ?
                ");
                $stmt->execute([$guideId]);
            }

            // Create or update verification record in user_verifications table
            $stmt = $this->pdo->prepare("
                INSERT INTO user_verifications (user_id, status, created_at)
                VALUES (?, 'Pending', NOW())
                ON DUPLICATE KEY UPDATE 
                    status = 'Pending',
                    created_at = NOW()
            ");
            $stmt->execute([$userId]);

            $response->json([
                'success' => true,
                'message' => 'Verification documents submitted successfully. Your documents are now under review.'
            ]);
        } catch (Exception $e) {
            error_log("Submit verification error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to submit verification documents'
            ], 500);
        }
    }

    /**
     * Get guide availability schedule
     */
    public function getAvailability(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get availability with booking status
            $stmt = $this->pdo->prepare("
                SELECT 
                    ga.availability_id,
                    ga.day_of_week,
                    ga.start_time,
                    ga.end_time,
                    CASE 
                        WHEN b.booking_id IS NOT NULL AND b.status IN ('Confirmed', 'In Progress') 
                        THEN 1 
                        ELSE 0 
                    END as is_booked,
                    b.booking_id
                FROM guideavailability ga
                LEFT JOIN bookings b ON (
                    b.guide_id = ga.guide_id 
                    AND b.booking_type = 'Guide'
                    AND DATE(b.start_date) = DATE(DATE_ADD(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 
                        CASE ga.day_of_week 
                            WHEN 'Monday' THEN 2
                            WHEN 'Tuesday' THEN 3
                            WHEN 'Wednesday' THEN 4
                            WHEN 'Thursday' THEN 5
                            WHEN 'Friday' THEN 6
                            WHEN 'Saturday' THEN 7
                            WHEN 'Sunday' THEN 1
                        END) DAY))
                    AND TIME(b.start_date) >= ga.start_time 
                    AND TIME(b.start_date) < ga.end_time
                    AND b.status IN ('Confirmed', 'In Progress')
                )
                WHERE ga.guide_id = ?
                ORDER BY 
                    CASE ga.day_of_week 
                        WHEN 'Monday' THEN 1
                        WHEN 'Tuesday' THEN 2
                        WHEN 'Wednesday' THEN 3
                        WHEN 'Thursday' THEN 4
                        WHEN 'Friday' THEN 5
                        WHEN 'Saturday' THEN 6
                        WHEN 'Sunday' THEN 7
                    END,
                    ga.start_time
            ");
            $stmt->execute([$guideId]);
            $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $availability
            ]);
        } catch (Exception $e) {
            error_log("Get availability error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch availability schedule'
            ], 500);
        }
    }

    /**
     * Update guide availability schedule
     */
    public function updateAvailability(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get request data
            $data = $request->json() ?? [];

            if (empty($data) || !is_array($data)) {
                $response->json([
                    'success' => false,
                    'message' => 'Invalid availability data'
                ], 400);
                return;
            }

            // Validate data
            $errors = [];
            foreach ($data as $index => $slot) {
                if (!isset($slot['day_of_week']) || !in_array($slot['day_of_week'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])) {
                    $errors[] = "Invalid day of week at index $index";
                }
                if (!isset($slot['start_time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $slot['start_time'])) {
                    $errors[] = "Invalid start time at index $index";
                }
                if (!isset($slot['end_time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $slot['end_time'])) {
                    $errors[] = "Invalid end time at index $index";
                }

                // Normalize time format to HH:MM:SS for database storage
                if (isset($slot['start_time']) && preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $slot['start_time'])) {
                    $slot['start_time'] .= ':00';
                }
                if (isset($slot['end_time']) && preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $slot['end_time'])) {
                    $slot['end_time'] .= ':00';
                }
                if (isset($slot['start_time']) && isset($slot['end_time']) && $slot['start_time'] >= $slot['end_time']) {
                    $errors[] = "End time must be after start time at index $index";
                }
            }

            if (!empty($errors)) {
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
                return;
            }

            // Start transaction
            $this->pdo->beginTransaction();

            try {
                // Clear ALL existing availability slots for this guide
                $clearStmt = $this->pdo->prepare("
                    DELETE FROM guideavailability 
                    WHERE guide_id = ?
                ");
                $clearStmt->execute([$guideId]);

                // Insert new availability slots
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO guideavailability (guide_id, day_of_week, start_time, end_time)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($data as $slot) {
                    $insertStmt->execute([
                        $guideId,
                        $slot['day_of_week'],
                        $slot['start_time'],
                        $slot['end_time']
                    ]);
                }

                $this->pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Availability schedule updated successfully'
                ]);
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Update availability error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to update availability schedule'
            ], 500);
        }
    }

    /**
     * Get guide images
     */
    public function getImages(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get guide images
            $stmt = $this->pdo->prepare("
                SELECT 
                    image_id,
                    image_path,
                    uploaded_at
                FROM guideimages 
                WHERE guide_id = ?
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([$guideId]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build image URLs
            foreach ($images as &$image) {
                $image['image_url'] = $this->buildImageUrl($image['image_path']);
            }

            $response->json([
                'success' => true,
                'data' => $images
            ]);
        } catch (Exception $e) {
            error_log("Get guide images error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch images'
            ], 500);
        }
    }

    /**
     * Upload guide images
     */
    public function uploadImages(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get uploaded files
            $files = $_FILES['images'] ?? [];

            if (empty($files) || !is_array($files['name'])) {
                $response->json([
                    'success' => false,
                    'message' => 'No images uploaded'
                ], 400);
                return;
            }

            // Validate files
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $response->json([
                        'success' => false,
                        'message' => 'File upload error'
                    ], 400);
                    return;
                }

                if (!in_array($files['type'][$i], $allowedTypes)) {
                    $response->json([
                        'success' => false,
                        'message' => 'Only JPG, PNG, and WebP images are allowed'
                    ], 400);
                    return;
                }

                if ($files['size'][$i] > $maxSize) {
                    $response->json([
                        'success' => false,
                        'message' => 'File size must be less than 5MB'
                    ], 400);
                    return;
                }
            }

            // Start transaction
            $this->pdo->beginTransaction();

            try {
                $fileService = new FileService();
                $uploadedImages = [];

                for ($i = 0; $i < count($files['name']); $i++) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    // Save image using FileService
                    $imagePath = $fileService->saveGuideGalleryPhoto($userId, $file);

                    if (!$imagePath) {
                        throw new Exception("Failed to save image");
                    }

                    // Insert into database
                    $stmt = $this->pdo->prepare("
                        INSERT INTO guideimages (guide_id, image_path)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$guideId, $imagePath]);

                    $uploadedImages[] = [
                        'image_id' => $this->pdo->lastInsertId(),
                        'image_path' => $imagePath,
                        'image_url' => $this->buildImageUrl($imagePath)
                    ];
                }

                $this->pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => count($uploadedImages) . ' image(s) uploaded successfully',
                    'data' => $uploadedImages
                ]);
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Upload guide images error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to upload images'
            ], 500);
        }
    }

    /**
     * Delete guide image
     */
    public function deleteImage(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get image ID from request
            $imageId = $request->get('id');

            if (!$imageId) {
                $response->json([
                    'success' => false,
                    'message' => 'Image ID is required'
                ], 400);
                return;
            }

            // Verify image ownership
            $stmt = $this->pdo->prepare("
                SELECT image_id, image_path 
                FROM guideimages 
                WHERE image_id = ? AND guide_id = ?
            ");
            $stmt->execute([$imageId, $guideId]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$image) {
                $response->json([
                    'success' => false,
                    'message' => 'Image not found or access denied'
                ], 404);
                return;
            }

            // Delete image file from filesystem
            $fileService = new FileService();
            $storagePath = $fileService->getStoragePath();
            $fullPath = $storagePath . '/' . $image['image_path'];

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete image record from database
            $deleteStmt = $this->pdo->prepare("
                DELETE FROM guideimages 
                WHERE image_id = ?
            ");
            $deleteStmt->execute([$imageId]);

            $response->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (Exception $e) {
            error_log("Delete guide image error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    /**
     * Get all available locations (camping and stargazing)
     */
    public function getAvailableLocations(Request $request, Response $response): void
    {
        try {
            // Get all camping destinations
            $campingStmt = $this->pdo->prepare("
                SELECT location_id, name, type, district, description, latitude, longitude
                FROM locations 
                WHERE type = 'Camping'
                ORDER BY name ASC
            ");
            $campingStmt->execute();
            $campingDestinations = $campingStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get all stargazing spots
            $stargazingStmt = $this->pdo->prepare("
                SELECT location_id, name, type, district, description, latitude, longitude
                FROM locations 
                WHERE type = 'Stargazing'
                ORDER BY name ASC
            ");
            $stargazingStmt->execute();
            $stargazingSpots = $stargazingStmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => [
                    'camping_destinations' => $campingDestinations,
                    'stargazing_spots' => $stargazingSpots
                ]
            ]);
        } catch (Exception $e) {
            error_log("Get available locations error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch available locations'
            ], 500);
        }
    }

    /**
     * Get guide's current location coverage
     */
    public function getGuideLocations(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get guide's current locations
            $stmt = $this->pdo->prepare("
                SELECT camping_destinations, stargazing_spots
                FROM guides
                WHERE guide_id = ?
            ");
            $stmt->execute([$guideId]);
            $guide = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$guide) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Parse camping destinations
            $campingDestinations = [];
            if (!empty($guide['camping_destinations'])) {
                $campingNames = explode(',', $guide['camping_destinations']);
                foreach ($campingNames as $name) {
                    $name = trim($name);
                    if (!empty($name)) {
                        // Get location details from locations table
                        $locationStmt = $this->pdo->prepare("
                            SELECT location_id, name, type, district, description, latitude, longitude
                            FROM locations
                            WHERE name = ? AND type = 'Camping'
                        ");
                        $locationStmt->execute([$name]);
                        $location = $locationStmt->fetch(PDO::FETCH_ASSOC);

                        if ($location) {
                            $campingDestinations[] = $location;
                        }
                    }
                }
            }

            // Parse stargazing spots
            $stargazingSpots = [];
            if (!empty($guide['stargazing_spots'])) {
                $stargazingNames = explode(',', $guide['stargazing_spots']);
                foreach ($stargazingNames as $name) {
                    $name = trim($name);
                    if (!empty($name)) {
                        // Get location details from locations table
                        $locationStmt = $this->pdo->prepare("
                            SELECT location_id, name, type, district, description, latitude, longitude
                            FROM locations
                            WHERE name = ? AND type = 'Stargazing'
                        ");
                        $locationStmt->execute([$name]);
                        $location = $locationStmt->fetch(PDO::FETCH_ASSOC);

                        if ($location) {
                            $stargazingSpots[] = $location;
                        }
                    }
                }
            }

            $response->json([
                'success' => true,
                'data' => [
                    'camping_destinations' => $campingDestinations,
                    'stargazing_spots' => $stargazingSpots
                ]
            ]);
        } catch (Exception $e) {
            error_log("Get guide locations error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch guide locations'
            ], 500);
        }
    }

    /**
     * Update guide's location coverage
     */
    public function updateGuideLocations(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get request data
            $data = $request->json() ?? [];

            if (empty($data)) {
                $response->json([
                    'success' => false,
                    'message' => 'Invalid location data'
                ], 400);
                return;
            }

            // Prepare location strings
            $campingDestinations = isset($data['camping_destinations']) && is_array($data['camping_destinations'])
                ? implode(',', array_filter($data['camping_destinations']))
                : '';

            $stargazingSpots = isset($data['stargazing_spots']) && is_array($data['stargazing_spots'])
                ? implode(',', array_filter($data['stargazing_spots']))
                : '';

            // Update guide's locations
            $stmt = $this->pdo->prepare("
                UPDATE guides 
                SET camping_destinations = ?, stargazing_spots = ?
                WHERE guide_id = ?
            ");
            $stmt->execute([$campingDestinations, $stargazingSpots, $guideId]);

            $response->json([
                'success' => true,
                'message' => 'Location coverage updated successfully'
            ]);
        } catch (Exception $e) {
            error_log("Update guide locations error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to update location coverage'
            ], 500);
        }
    }

    /**
     * Check if a location can be removed (has active bookings)
     */
    public function checkLocationRemoval(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get location name from request
            $locationName = $request->get('location_name');

            if (!$locationName) {
                $response->json([
                    'success' => false,
                    'message' => 'Location name is required'
                ], 400);
                return;
            }

            // Check for active bookings with this location
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as active_bookings
                FROM bookings b
                WHERE b.guide_id = ? 
                AND b.booking_type = 'Guide'
                AND b.status IN ('Confirmed', 'In Progress')
                AND (
                    b.start_date >= CURDATE() OR 
                    b.end_date >= CURDATE()
                )
            ");
            $stmt->execute([$guideId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $canRemove = $result['active_bookings'] == 0;

            $response->json([
                'success' => true,
                'data' => [
                    'can_remove' => $canRemove,
                    'message' => $canRemove
                        ? 'This location can be removed.'
                        : 'This location cannot be removed because you have active bookings.'
                ]
            ]);
        } catch (Exception $e) {
            error_log("Check location removal error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to check location removal'
            ], 500);
        }
    }

    /**
     * Get guide's bookings (past and pending)
     */
    public function getGuideBookings(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get past bookings (Completed)
            $pastStmt = $this->pdo->prepare("
                SELECT 
                    b.booking_id,
                    b.booking_date,
                    b.start_date,
                    b.end_date,
                    b.total_amount,
                    b.advance_paid,
                    b.status,
                    b.last_status_updated_by,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    c.phone_number as customer_phone,
                    l.name as location_name,
                    l.description as trip_description
                FROM bookings b
                LEFT JOIN customers c ON b.customer_id = c.customer_id
                LEFT JOIN locations l ON l.name = (
                    SELECT CASE 
                        WHEN b.start_date = CURDATE() THEN 
                            (SELECT name FROM locations WHERE type = 'Camping' LIMIT 1)
                        ELSE 
                            (SELECT name FROM locations WHERE type = 'Stargazing' LIMIT 1)
                    END
                )
                WHERE b.guide_id = ? 
                AND b.booking_type = 'Guide'
                AND b.status = 'Completed'
                ORDER BY b.start_date DESC
            ");
            $pastStmt->execute([$guideId]);
            $pastBookings = $pastStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pending bookings (Confirmed)
            $pendingStmt = $this->pdo->prepare("
                SELECT 
                    b.booking_id,
                    b.booking_date,
                    b.start_date,
                    b.end_date,
                    b.total_amount,
                    b.advance_paid,
                    b.status,
                    b.last_status_updated_by,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    c.phone_number as customer_phone,
                    l.name as location_name,
                    l.description as trip_description
                FROM bookings b
                LEFT JOIN customers c ON b.customer_id = c.customer_id
                LEFT JOIN locations l ON l.name = (
                    SELECT CASE 
                        WHEN b.start_date = CURDATE() THEN 
                            (SELECT name FROM locations WHERE type = 'Camping' LIMIT 1)
                        ELSE 
                            (SELECT name FROM locations WHERE type = 'Stargazing' LIMIT 1)
                    END
                )
                WHERE b.guide_id = ? 
                AND b.booking_type = 'Guide'
                AND b.status = 'Confirmed'
                ORDER BY b.start_date ASC
            ");
            $pendingStmt->execute([$guideId]);
            $pendingBookings = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => [
                    'past_bookings' => $pastBookings,
                    'pending_bookings' => $pendingBookings
                ]
            ]);
        } catch (Exception $e) {
            error_log("Get guide bookings error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch guide bookings'
            ], 500);
        }
    }

    /**
     * Mark booking as finished/completed
     */
    public function markBookingAsFinished(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get guide ID from user ID
            $guideId = $this->getGuideIdByUserId($userId);

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide profile not found'
                ], 404);
                return;
            }

            // Get booking ID from request
            $bookingId = $request->get('booking_id');

            if (!$bookingId) {
                $response->json([
                    'success' => false,
                    'message' => 'Booking ID is required'
                ], 400);
                return;
            }

            // Verify the booking belongs to this guide and is in Confirmed status
            $verifyStmt = $this->pdo->prepare("
                SELECT booking_id, status, guide_id
                FROM bookings
                WHERE booking_id = ? AND guide_id = ? AND booking_type = 'Guide'
            ");
            $verifyStmt->execute([$bookingId, $guideId]);
            $booking = $verifyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $response->json([
                    'success' => false,
                    'message' => 'Booking not found or does not belong to you'
                ], 404);
                return;
            }

            if ($booking['status'] !== 'Confirmed') {
                $response->json([
                    'success' => false,
                    'message' => 'Only confirmed bookings can be marked as finished'
                ], 400);
                return;
            }

            // Update booking status to Completed
            $updateStmt = $this->pdo->prepare("
                UPDATE bookings 
                SET status = 'Completed', last_status_updated_by = 'Guide'
                WHERE booking_id = ?
            ");
            $updateStmt->execute([$bookingId]);

            $response->json([
                'success' => true,
                'message' => 'Trip marked as finished successfully'
            ]);
        } catch (Exception $e) {
            error_log("Mark booking as finished error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to mark trip as finished'
            ], 500);
        }
    }

    /**
     * Create notification for guide
     */
    private function createNotification(int $userId, string $type, string $message): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, message, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$userId, $type, $message]);
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }

    /**
     * Create test notifications for guides
     */
    public function createTestNotifications(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists and has the right role
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Guide'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Create test notifications
            $testNotifications = [
                [
                    'type' => 'Verification',
                    'message' => 'Your NIC verification has been approved! You can now receive bookings.'
                ],
                [
                    'type' => 'BookingCreated',
                    'message' => 'New booking received! John Doe has booked your guide service for December 15, 2024.'
                ],
                [
                    'type' => 'PaymentSuccess',
                    'message' => 'Payment received! LKR 7,000 has been credited to your account for booking #123.'
                ],
                [
                    'type' => 'BookingCompleted',
                    'message' => 'Booking #123 has been completed. Thank you for providing excellent service!'
                ]
            ];

            foreach ($testNotifications as $notification) {
                $this->createNotification($userId, $notification['type'], $notification['message']);
            }

            // Get updated unread count
            $countStmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0
            ");
            $countStmt->execute([$userId]);
            $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

            $response->json([
                'success' => true,
                'message' => 'Test notifications created successfully',
                'data' => [
                    'unread_count' => (int)$unreadCount
                ]
            ]);
        } catch (Exception $e) {
            error_log("Create test notifications error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to create test notifications'
            ], 500);
        }
    }

    /**
     * Build public URL for image
     */
    private function buildImageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // Normalize path separators
        $normalizedPath = str_replace('\\', '/', $imagePath);

        // If it's already a full URL, return as-is
        if (strpos($normalizedPath, 'http') === 0) {
            return $normalizedPath;
        }

        // Remove storage/uploads prefix if present
        if (strpos($normalizedPath, 'storage/uploads/') !== false) {
            $normalizedPath = substr($normalizedPath, strpos($normalizedPath, 'storage/uploads/') + 16);
        }

        // Build the public URL
        $baseUrl = 'http://localhost/skycamp/skycamp-backend/storage/uploads/';
        return $baseUrl . $normalizedPath . '?ts=' . time();
    }
}
