<?php

/**
 * Renter Dashboard Controller
 * Handles renter dashboard-related API endpoints
 */

use App\Services\NotificationService;

class RenterDashboardController extends Controller
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
     * Get renter dashboard statistics
     * GET /api/renter/dashboard/stats
     */
    public function getDashboardStats(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get renter ID from user ID
            $renterId = $this->getRenterIdByUserId($userId);

            if (!$renterId) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter profile not found'
                ], 404);
                return;
            }

            // Get dashboard statistics
            $stats = $this->calculateDashboardStats($renterId);

            $response->json([
                'success' => true,
                'data' => $stats
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching renter dashboard stats: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics'
            ], 500);
        }
    }

    /**
     * Get renter ID by user ID
     */
    private function getRenterIdByUserId(int $userId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT renter_id FROM renters WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['renter_id'] : null;
    }

    /**
     * Calculate dashboard statistics for a renter
     */
    private function calculateDashboardStats(int $renterId): array
    {
        // 1. Total Equipment Listed (Active items)
        $totalEquipment = $this->getTotalEquipment($renterId);

        // 2. Total Successful Bookings (Completed bookings)
        $totalSuccessfulBookings = $this->getTotalSuccessfulBookings($renterId);

        // 3. Total Pending Bookings (Confirmed bookings that haven't ended yet)
        $totalPendingBookings = $this->getTotalPendingBookings($renterId);

        // 4. Total Customer Reviews Received
        $totalReviews = $this->getTotalReviews($renterId);

        return [
            'totalEquipment' => $totalEquipment,
            'totalSuccessfulBookings' => $totalSuccessfulBookings,
            'totalPendingBookings' => $totalPendingBookings,
            'totalReviews' => $totalReviews
        ];
    }

    /**
     * Get total active equipment count for renter
     */
    private function getTotalEquipment(int $renterId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM renterequipment 
            WHERE renter_id = ? AND status = 'Active'
        ");
        $stmt->execute([$renterId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get total successful bookings (completed bookings)
     */
    private function getTotalSuccessfulBookings(int $renterId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE renter_id = ? AND status = 'Completed'
        ");
        $stmt->execute([$renterId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get total pending bookings (confirmed bookings that haven't ended yet)
     */
    private function getTotalPendingBookings(int $renterId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE renter_id = ? 
            AND status = 'Confirmed' 
            AND end_date >= CURDATE()
        ");
        $stmt->execute([$renterId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get total reviews received for this renter
     */
    private function getTotalReviews(int $renterId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reviews 
            WHERE entity_type = 'Renter' 
            AND entity_id = ? 
            AND status = 'Active'
        ");
        $stmt->execute([$renterId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    /**
     * Get renter profile details
     * GET /api/renter/profile
     */
    public function getProfile(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get renter profile with user data
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.*,
                    u.email,
                    u.created_at as user_created_at
                FROM renters r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.user_id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$profile) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter profile not found'
                ], 404);
                return;
            }

            $response->json([
                'success' => true,
                'data' => $profile
            ]);
        } catch (Exception $e) {
            error_log("RenterDashboardController::getProfile - Error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }

    /**
     * Update renter profile
     * POST /api/renter/profile
     */
    public function updateProfile(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get renter ID
            $renterId = $this->getRenterIdByUserId($userId);

            if (!$renterId) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter profile not found'
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

            // Update renter profile
            $updateFields = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $phoneNumber,
                'home_address' => $homeAddress,
                'gender' => $gender,
                'dob' => $dob ?: null,
                'latitude' => $latitude ?: null,
                'longitude' => $longitude ?: null
            ];

            if ($profilePicturePath) {
                $updateFields['profile_picture'] = $profilePicturePath;
            }

            $setClause = implode(', ', array_map(fn($field) => "$field = ?", array_keys($updateFields)));
            $values = array_values($updateFields);
            $values[] = $renterId;

            $stmt = $this->pdo->prepare("UPDATE renters SET $setClause WHERE renter_id = ?");
            $stmt->execute($values);

            // Get updated profile
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.*,
                    u.email,
                    u.created_at as user_created_at
                FROM renters r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.renter_id = ?
            ");
            $stmt->execute([$renterId]);
            $updatedProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedProfile
            ]);
        } catch (Exception $e) {
            error_log("RenterDashboardController::updateProfile - Error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    /**
     * Get verification documents for the logged-in renter
     */
    public function getVerificationDocs(Request $request, Response $response)
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get renter ID from user ID
            $renterId = $this->getRenterIdByUserId($userId);

            if (!$renterId) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter profile not found'
                ], 404);
                return;
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    verification_status,
                    nic_front_image,
                    nic_back_image
                FROM renters 
                WHERE renter_id = ?
            ");
            $stmt->execute([$renterId]);
            $renter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$renter) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter not found'
                ], 404);
                return;
            }

            // Build image URLs
            $nicFrontImageUrl = null;
            $nicBackImageUrl = null;

            if ($renter['nic_front_image']) {
                $nicFrontImageUrl = $this->buildImageUrl($renter['nic_front_image']);
            }

            if ($renter['nic_back_image']) {
                $nicBackImageUrl = $this->buildImageUrl($renter['nic_back_image']);
            }

            $response->json([
                'success' => true,
                'data' => [
                    'verification_status' => $renter['verification_status'],
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

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            // Get renter ID from user ID
            $renterId = $this->getRenterIdByUserId($userId);

            if (!$renterId) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter profile not found'
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
                // Check if renter already has NIC images
                $stmt = $this->pdo->prepare("
                    SELECT nic_front_image, nic_back_image 
                    FROM renters 
                    WHERE renter_id = ?
                ");
                $stmt->execute([$renterId]);
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

                // Update renter record with new images
                $stmt = $this->pdo->prepare("
                    UPDATE renters 
                    SET 
                        nic_front_image = ?,
                        nic_back_image = ?,
                        verification_status = 'Pending'
                    WHERE renter_id = ?
                ");
                $stmt->execute([$nicFrontPath, $nicBackPath, $renterId]);
            } else {
                // Submit existing images for verification
                $stmt = $this->pdo->prepare("
                    UPDATE renters 
                    SET 
                        verification_status = 'Pending'
                    WHERE renter_id = ?
                ");
                $stmt->execute([$renterId]);
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

    /**
     * Reformat PHP file array to handle multiple uploads
     */
    private function reformatFileArray($fileArray)
    {
        $files = [];
        $fileCount = count($fileArray['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $files[] = [
                'name' => $fileArray['name'][$i],
                'type' => $fileArray['type'][$i],
                'tmp_name' => $fileArray['tmp_name'][$i],
                'error' => $fileArray['error'][$i],
                'size' => $fileArray['size'][$i]
            ];
        }

        return $files;
    }

    /**
     * Get all available equipment categories and items
     * GET /api/renter/equipment/catalog
     */
    public function getEquipmentCatalog(Request $request, Response $response): void
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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

            // Get equipment categories with their items in specific order
            $stmt = $this->pdo->prepare("
                SELECT 
                    ec.category_id,
                    ec.type,
                    ec.name as category_name,
                    ec.description as category_description,
                    e.equipment_id,
                    e.name as equipment_name,
                    e.description as equipment_description
                FROM equipment_categories ec
                LEFT JOIN equipment e ON ec.category_id = e.category_id AND e.status = 'Active'
                ORDER BY 
                    CASE ec.type 
                        WHEN 'Camping' THEN 1 
                        WHEN 'Stargazing' THEN 2 
                        ELSE 3 
                    END,
                    CASE ec.category_id
                        -- Camping categories in order
                        WHEN 1 THEN 1   -- Tents
                        WHEN 2 THEN 2   -- Sleeping Gear
                        WHEN 3 THEN 3   -- Cooking & Kitchen Items
                        WHEN 4 THEN 4   -- Camping Furniture
                        WHEN 5 THEN 5   -- Lights
                        WHEN 6 THEN 6   -- Navigation & Safety Tools
                        WHEN 7 THEN 7   -- Water & Hydration
                        WHEN 8 THEN 8   -- Bags & Storage
                        WHEN 9 THEN 9   -- Clothing
                        WHEN 10 THEN 10 -- Fun & Extras
                        WHEN 11 THEN 11 -- Power & Charging
                        -- Stargazing categories in order
                        WHEN 12 THEN 1  -- Binoculars
                        WHEN 13 THEN 2  -- Telescopes
                        WHEN 14 THEN 3  -- Tripods & Mounts
                        WHEN 15 THEN 4  -- Accessories
                        ELSE 99
                    END,
                    CASE e.equipment_id
                        -- Tents (category 1)
                        WHEN 1 THEN 1   -- 1-person tent
                        WHEN 2 THEN 2   -- 2-person tent
                        WHEN 3 THEN 3   -- 3 or more person tent
                        WHEN 48 THEN 4  -- 4-person tent
                        -- Sleeping Gear (category 2)
                        WHEN 4 THEN 1   -- Sleeping bags
                        WHEN 5 THEN 2   -- Air mattress
                        WHEN 6 THEN 3   -- Camping pillow
                        WHEN 7 THEN 4   -- Emergency blanket
                        -- Cooking & Kitchen Items (category 3)
                        WHEN 8 THEN 1   -- Single gas stove
                        WHEN 9 THEN 2   -- Double gas stove
                        WHEN 10 THEN 3  -- Gas BBQ grill
                        WHEN 11 THEN 4  -- Cooking pot and pan set
                        WHEN 12 THEN 5  -- Kettle for boiling water
                        WHEN 13 THEN 6  -- Fork, spoon, knife set
                        WHEN 14 THEN 7  -- Chopping board
                        WHEN 15 THEN 8  -- Reusable plates and bowls
                        WHEN 16 THEN 9  -- Food storage containers
                        WHEN 17 THEN 10 -- Cooler box
                        -- Camping Furniture (category 4)
                        WHEN 18 THEN 1  -- Camping chair
                        WHEN 19 THEN 2  -- Folding table
                        WHEN 20 THEN 3  -- Hammock
                        -- Lights (category 5)
                        WHEN 21 THEN 1  -- Camping lanterns
                        WHEN 22 THEN 2  -- Torch
                        WHEN 23 THEN 3  -- Tent hanging light
                        -- Navigation & Safety Tools (category 6)
                        WHEN 24 THEN 1  -- Compass & Map
                        WHEN 25 THEN 2  -- Emergency whistle
                        WHEN 26 THEN 3  -- First-aid kit
                        WHEN 27 THEN 4  -- Walkie-talkies
                        -- Water & Hydration (category 7)
                        WHEN 28 THEN 1  -- Water bottles
                        WHEN 29 THEN 2  -- Water jugs
                        -- Bags & Storage (category 8)
                        WHEN 30 THEN 1  -- Hiking backpacks
                        WHEN 31 THEN 2  -- Dry bags
                        WHEN 32 THEN 3  -- Waterproof pouches
                        WHEN 33 THEN 4  -- Gear organizer bag
                        -- Clothing (category 9)
                        WHEN 34 THEN 1  -- Raincoat
                        WHEN 35 THEN 2  -- Warm jacket
                        WHEN 36 THEN 3  -- Waterproof shoes
                        -- Fun & Extras (category 10)
                        WHEN 37 THEN 1  -- Card games / Board games
                        WHEN 38 THEN 2  -- Travel guitar
                        -- Power & Charging (category 11)
                        WHEN 39 THEN 1  -- Power bank & Cables
                        -- Stargazing Binoculars (category 12)
                        WHEN 40 THEN 1  -- Small binoculars
                        WHEN 41 THEN 2  -- Stargazing binoculars
                        -- Stargazing Telescopes (category 13)
                        WHEN 42 THEN 1  -- Beginner telescope
                        WHEN 43 THEN 2  -- Big telescope
                        -- Tripods & Mounts (category 14)
                        WHEN 44 THEN 1  -- Tripod stands for telescope or binoculars
                        -- Accessories (category 15)
                        WHEN 45 THEN 1  -- Star maps or books
                        WHEN 46 THEN 2  -- Power bank for telescope
                        WHEN 47 THEN 3  -- Laser pointer for pointing at stars
                        ELSE 99
                    END
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group by category
            $catalog = [];
            foreach ($results as $row) {
                $categoryId = $row['category_id'];
                if (!isset($catalog[$categoryId])) {
                    $catalog[$categoryId] = [
                        'category_id' => $row['category_id'],
                        'type' => $row['type'],
                        'name' => $row['category_name'],
                        'description' => $row['category_description'],
                        'equipment' => []
                    ];
                }

                if ($row['equipment_id']) {
                    $catalog[$categoryId]['equipment'][] = [
                        'equipment_id' => $row['equipment_id'],
                        'name' => $row['equipment_name'],
                        'description' => $row['equipment_description']
                    ];
                }
            }

            $response->json([
                'success' => true,
                'data' => array_values($catalog)
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching equipment catalog: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch equipment catalog'
            ], 500);
        }
    }

    /**
     * Get renter's equipment list
     * GET /api/renter/equipment/list
     */
    public function getRenterEquipment(Request $request, Response $response): void
    {
        try {
            // Get current user from session
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }
            $renterId = $this->getRenterIdByUserId($this->session->get('user_id'));

            // Get renter's equipment with photos (both active and archived)
            $stmt = $this->pdo->prepare("
                SELECT 
                    re.renter_equipment_id,
                    re.renter_id,
                    re.equipment_id,
                    re.item_condition,
                    re.price_per_day,
                    re.stock_quantity,
                    re.status,
                    e.name as equipment_name,
                    e.description as equipment_description,
                    ec.name as category_name,
                    ec.type as category_type
                FROM renterequipment re
                INNER JOIN equipment e ON re.equipment_id = e.equipment_id
                INNER JOIN equipment_categories ec ON e.category_id = ec.category_id
                WHERE re.renter_id = ?
                ORDER BY re.status DESC, e.name
            ");
            $stmt->execute([$renterId]);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get photos for each equipment item
            foreach ($equipment as &$item) {
                $photoStmt = $this->pdo->prepare("
                    SELECT photo_id, photo_path, uploaded_at, COALESCE(display_order, photo_id) as display_order
                    FROM renterequipmentphotos
                    WHERE renter_equipment_id = ?
                    ORDER BY COALESCE(display_order, photo_id) ASC
                ");
                $photoStmt->execute([$item['renter_equipment_id']]);
                $photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);

                $item['photos'] = array_map(function ($photo) {
                    return [
                        'photo_id' => $photo['photo_id'],
                        'photo_path' => $photo['photo_path'],
                        'photo_url' => $this->buildImageUrl($photo['photo_path']),
                        'uploaded_at' => $photo['uploaded_at']
                    ];
                }, $photos);
            }

            $response->json([
                'success' => true,
                'data' => $equipment
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching renter equipment: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch equipment list'
            ], 500);
        }
    }

    /**
     * Add new equipment to renter's inventory
     * POST /api/renter/equipment/add
     */
    public function addEquipment(Request $request, Response $response): void
    {
        error_log("🚨 METHOD CALLED: addEquipment method executed at " . date('Y-m-d H:i:s'));
        try {
            error_log("📸 PHOTO UPLOAD: Starting equipment addition process...");

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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                error_log("📸 PHOTO UPLOAD: Authentication failed");
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }

            error_log("📸 PHOTO UPLOAD: User authenticated - ID: " . $userId);
            $renterId = $this->getRenterIdByUserId($userId);

            // Get form data
            $data = $request->getFormData();

            // Validate required fields
            $equipmentId = $data['equipment_id'] ?? null;
            $itemCondition = $data['item_condition'] ?? null;
            $pricePerDay = $data['price_per_day'] ?? null;
            $stockQuantity = $data['stock_quantity'] ?? 1;
            // Get condition photos directly from $_FILES since Request::file() doesn't handle arrays
            $conditionPhotos = $_FILES['condition_photos'] ?? null;

            error_log("📸 PHOTO UPLOAD: Form data received: " . json_encode($data));
            error_log("📸 PHOTO UPLOAD: Files received: " . json_encode($_FILES));
            error_log("📸 PHOTO UPLOAD: condition_photos from \$_FILES: " . json_encode($conditionPhotos));

            if (!$equipmentId || !$itemCondition || !$pricePerDay) {
                $response->json([
                    'success' => false,
                    'message' => 'Equipment, condition, and price are required'
                ], 400);
                return;
            }

            // Validate numeric values
            if (!is_numeric($pricePerDay) || $pricePerDay <= 0) {
                $response->json([
                    'success' => false,
                    'message' => 'Price per day must be a positive number'
                ], 400);
                return;
            }

            if (!is_numeric($stockQuantity) || $stockQuantity < 1) {
                $response->json([
                    'success' => false,
                    'message' => 'Stock quantity must be at least 1'
                ], 400);
                return;
            }

            // Validate equipment exists
            $equipmentStmt = $this->pdo->prepare("
                SELECT equipment_id, name FROM equipment 
                WHERE equipment_id = ? AND status = 'Active'
            ");
            $equipmentStmt->execute([$equipmentId]);
            $equipment = $equipmentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$equipment) {
                $response->json([
                    'success' => false,
                    'message' => 'Selected equipment not found'
                ], 400);
                return;
            }

            // Check if renter already has this equipment
            $existingStmt = $this->pdo->prepare("
                SELECT renter_equipment_id FROM renterequipment 
                WHERE renter_id = ? AND equipment_id = ? AND status = 'Active'
            ");
            $existingStmt->execute([$renterId, $equipmentId]);

            if ($existingStmt->fetch()) {
                $response->json([
                    'success' => false,
                    'message' => 'You already have this equipment listed'
                ], 400);
                return;
            }

            // Start transaction
            $this->pdo->beginTransaction();

            try {
                // Insert equipment record
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO renterequipment (
                        renter_id, equipment_id, item_condition, 
                        price_per_day, stock_quantity, status
                    ) VALUES (?, ?, ?, ?, ?, 'Active')
                ");
                $insertStmt->execute([
                    $renterId,
                    $equipmentId,
                    $itemCondition,
                    $pricePerDay,
                    $stockQuantity
                ]);

                $renterEquipmentId = $this->pdo->lastInsertId();

                // Handle condition photos
                $uploadedPhotos = [];
                error_log("📸 PHOTO UPLOAD: Processing condition photos...");

                if ($conditionPhotos && is_array($conditionPhotos)) {
                    error_log("📸 PHOTO UPLOAD: Condition photos array found");
                    $fileService = new FileService();

                    // Handle multiple file uploads (array format)
                    $files = isset($conditionPhotos['name']) && is_array($conditionPhotos['name'])
                        ? $this->reformatFileArray($conditionPhotos)
                        : [$conditionPhotos];

                    error_log("📸 PHOTO UPLOAD: Processing " . count($files) . " photo files");

                    foreach ($files as $index => $photo) {
                        error_log("📸 PHOTO UPLOAD: Processing photo " . ($index + 1) . " - Error: " . $photo['error']);

                        if ($photo['error'] === UPLOAD_ERR_OK) {
                            error_log("📸 PHOTO UPLOAD: Photo " . ($index + 1) . " upload OK, saving to file system...");

                            // Use new equipment photo method with equipment ID and photo number
                            $photoPath = $fileService->saveEquipmentPhoto(
                                $userId,
                                $renterEquipmentId,
                                $photo,
                                $index + 1
                            );

                            if ($photoPath) {
                                error_log("📸 PHOTO UPLOAD: Photo " . ($index + 1) . " saved to: " . $photoPath);

                                // Insert photo record into renterequipmentphotos table
                                error_log("📸 PHOTO UPLOAD: Inserting photo record into renterequipmentphotos table...");
                                $photoStmt = $this->pdo->prepare("
                                    INSERT INTO renterequipmentphotos (
                                        renter_equipment_id, photo_path
                                    ) VALUES (?, ?)
                                ");
                                $photoStmt->execute([$renterEquipmentId, $photoPath]);

                                error_log("📸 PHOTO UPLOAD: Photo record inserted successfully - ID: " . $this->pdo->lastInsertId());

                                $uploadedPhotos[] = [
                                    'photo_path' => $photoPath,
                                    'photo_url' => $this->buildImageUrl($photoPath)
                                ];
                            } else {
                                error_log("📸 PHOTO UPLOAD: Photo " . ($index + 1) . " failed to save to file system");
                            }
                        } else {
                            error_log("📸 PHOTO UPLOAD: Photo " . ($index + 1) . " upload error: " . $photo['error']);
                        }
                    }
                } else {
                    error_log("📸 PHOTO UPLOAD: No condition photos provided");
                }

                error_log("📸 PHOTO UPLOAD: Total photos uploaded: " . count($uploadedPhotos));

                $this->pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Equipment added successfully',
                    'data' => [
                        'renter_equipment_id' => $renterEquipmentId,
                        'equipment_name' => $equipment['name'],
                        'photos' => $uploadedPhotos
                    ]
                ], 201);
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->log("Error adding equipment: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to add equipment'
            ], 500);
        }
    }

    /**
     * Update equipment details
     * PUT /api/renter/equipment/update/{id}
     */
    public function updateEquipment(Request $request, Response $response): void
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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
            $renterId = $this->getRenterIdByUserId($userId);

            // Get URL parameter and form data
            $equipmentId = $request->get('id'); // URL parameter

            // For PUT requests, parse JSON data from request body
            $data = $request->json() ?? [];

            $itemCondition = $data['item_condition'] ?? null;
            $pricePerDay = $data['price_per_day'] ?? null;
            $stockQuantity = $data['stock_quantity'] ?? null;
            $status = $data['status'] ?? null;

            error_log("🔄 UPDATE: Parsed JSON data - item_condition: " . $itemCondition . ", price_per_day: " . $pricePerDay . ", stock_quantity: " . $stockQuantity);

            // Validate equipment ownership
            $ownershipStmt = $this->pdo->prepare("
                SELECT renter_equipment_id FROM renterequipment 
                WHERE renter_equipment_id = ? AND renter_id = ?
            ");
            $ownershipStmt->execute([$equipmentId, $renterId]);

            if (!$ownershipStmt->fetch()) {
                $response->json([
                    'success' => false,
                    'message' => 'Equipment not found or access denied'
                ], 404);
                return;
            }

            // Handle new photo uploads if provided
            $newPhotos = $_FILES['new_photos'] ?? null;
            if ($newPhotos && is_array($newPhotos)) {
                error_log("🖼️ UPDATE: Processing new photos for equipment ID: " . $equipmentId);

                $fileService = new FileService();

                // Handle multiple file uploads (array format)
                $files = isset($newPhotos['name']) && is_array($newPhotos['name'])
                    ? $this->reformatFileArray($newPhotos)
                    : [$newPhotos];

                error_log("🖼️ UPDATE: Processing " . count($files) . " new photo files");

                foreach ($files as $index => $photo) {
                    error_log("🖼️ UPDATE: Processing new photo " . ($index + 1) . " - Error: " . $photo['error']);

                    if ($photo['error'] === UPLOAD_ERR_OK) {
                        error_log("🖼️ UPDATE: New photo " . ($index + 1) . " upload OK, saving to file system...");

                        // Get the next available photo number (accounting for deleted photos)
                        $photoNumberStmt = $this->pdo->prepare("
                            SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(photo_path, '_', -1), '.', 1) AS UNSIGNED)), 0) + 1 as next_number
                            FROM renterequipmentphotos 
                            WHERE renter_equipment_id = ? AND photo_path LIKE ?
                        ");
                        $photoNumberStmt->execute([$equipmentId, "users/{$userId}/equipment/{$equipmentId}_%.jpg"]);
                        $nextPhotoNumber = $photoNumberStmt->fetch(PDO::FETCH_ASSOC)['next_number'] + $index;

                        error_log("🖼️ UPDATE: Equipment {$equipmentId} - Current max photo number: " . ($nextPhotoNumber - $index) . ", New photo number: {$nextPhotoNumber}");

                        // Use new equipment photo method with equipment ID and next photo number
                        $photoPath = $fileService->saveEquipmentPhoto(
                            $userId,
                            $equipmentId,
                            $photo,
                            $nextPhotoNumber
                        );

                        if ($photoPath) {
                            error_log("🖼️ UPDATE: New photo " . ($index + 1) . " saved to: " . $photoPath);

                            // Insert photo record into renterequipmentphotos table
                            error_log("🖼️ UPDATE: Inserting new photo record into renterequipmentphotos table...");
                            $photoStmt = $this->pdo->prepare("
                                INSERT INTO renterequipmentphotos (
                                    renter_equipment_id, photo_path
                                ) VALUES (?, ?)
                            ");
                            $photoStmt->execute([$equipmentId, $photoPath]);

                            error_log("🖼️ UPDATE: New photo record inserted successfully - ID: " . $this->pdo->lastInsertId());
                        } else {
                            error_log("🖼️ UPDATE: New photo " . ($index + 1) . " failed to save to file system");
                        }
                    } else {
                        error_log("🖼️ UPDATE: New photo " . ($index + 1) . " upload error: " . $photo['error']);
                    }
                }
            }

            // Build update query dynamically
            $updateFields = [];
            $updateValues = [];

            if ($itemCondition !== null) {
                $updateFields[] = "item_condition = ?";
                $updateValues[] = $itemCondition;
            }

            if ($pricePerDay !== null) {
                if (!is_numeric($pricePerDay) || $pricePerDay <= 0) {
                    $response->json([
                        'success' => false,
                        'message' => 'Price per day must be a positive number'
                    ], 400);
                    return;
                }
                $updateFields[] = "price_per_day = ?";
                $updateValues[] = $pricePerDay;
            }

            if ($stockQuantity !== null) {
                if (!is_numeric($stockQuantity) || $stockQuantity < 0) {
                    $response->json([
                        'success' => false,
                        'message' => 'Stock quantity must be a non-negative number'
                    ], 400);
                    return;
                }
                $updateFields[] = "stock_quantity = ?";
                $updateValues[] = $stockQuantity;
            }

            if ($status !== null && in_array($status, ['Active', 'Archived'])) {
                $updateFields[] = "status = ?";
                $updateValues[] = $status;
            }

            if (empty($updateFields)) {
                $response->json([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ], 400);
                return;
            }

            $updateValues[] = $equipmentId;

            $updateStmt = $this->pdo->prepare("
                UPDATE renterequipment 
                SET " . implode(', ', $updateFields) . "
                WHERE renter_equipment_id = ?
            ");
            $updateStmt->execute($updateValues);

            $response->json([
                'success' => true,
                'message' => 'Equipment updated successfully'
            ], 200);
        } catch (Exception $e) {
            $this->log("Error updating equipment: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to update equipment'
            ], 500);
        }
    }

    /**
     * Update equipment with photos (POST method for FormData)
     * POST /api/renter/equipment/update/:id
     */
    public function updateEquipmentWithPhotos(Request $request, Response $response): void
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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
            $renterId = $this->getRenterIdByUserId($userId);

            // Get URL parameter and form data
            $equipmentId = $request->get('id'); // URL parameter
            $data = $request->getFormData(); // This works for POST requests

            $itemCondition = $data['item_condition'] ?? null;
            $pricePerDay = $data['price_per_day'] ?? null;
            $stockQuantity = $data['stock_quantity'] ?? null;
            $status = $data['status'] ?? null;

            error_log("🔄 UPDATE PHOTOS: Parsed data - item_condition: " . $itemCondition . ", price_per_day: " . $pricePerDay . ", stock_quantity: " . $stockQuantity);

            // Validate equipment ownership
            $ownershipStmt = $this->pdo->prepare("
                SELECT renter_equipment_id FROM renterequipment 
                WHERE renter_equipment_id = ? AND renter_id = ?
            ");
            $ownershipStmt->execute([$equipmentId, $renterId]);

            if (!$ownershipStmt->fetch()) {
                $response->json([
                    'success' => false,
                    'message' => 'Equipment not found or access denied'
                ], 404);
                return;
            }

            // Handle new photo uploads if provided
            $newPhotos = $_FILES['new_photos'] ?? null;
            if ($newPhotos && is_array($newPhotos)) {
                error_log("🖼️ UPDATE PHOTOS: Processing new photos for equipment ID: " . $equipmentId);

                $fileService = new FileService();

                // Handle multiple file uploads (array format)
                $files = isset($newPhotos['name']) && is_array($newPhotos['name'])
                    ? $this->reformatFileArray($newPhotos)
                    : [$newPhotos];

                error_log("🖼️ UPDATE PHOTOS: Processing " . count($files) . " new photo files");

                foreach ($files as $index => $photo) {
                    error_log("🖼️ UPDATE PHOTOS: Processing new photo " . ($index + 1) . " - Error: " . $photo['error']);

                    if ($photo['error'] === UPLOAD_ERR_OK) {
                        error_log("🖼️ UPDATE PHOTOS: New photo " . ($index + 1) . " upload OK, saving to file system...");

                        // Get the next available photo number (accounting for deleted photos)
                        $photoNumberStmt = $this->pdo->prepare("
                            SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(photo_path, '_', -1), '.', 1) AS UNSIGNED)), 0) + 1 as next_number
                            FROM renterequipmentphotos 
                            WHERE renter_equipment_id = ? AND photo_path LIKE ?
                        ");
                        $photoNumberStmt->execute([$equipmentId, "users/{$userId}/equipment/{$equipmentId}_%.jpg"]);
                        $nextPhotoNumber = $photoNumberStmt->fetch(PDO::FETCH_ASSOC)['next_number'] + $index;

                        error_log("🖼️ UPDATE PHOTOS: Equipment {$equipmentId} - Current max photo number: " . ($nextPhotoNumber - $index) . ", New photo number: {$nextPhotoNumber}");

                        // Use new equipment photo method with equipment ID and next photo number
                        $photoPath = $fileService->saveEquipmentPhoto(
                            $userId,
                            $equipmentId,
                            $photo,
                            $nextPhotoNumber
                        );

                        if ($photoPath) {
                            error_log("🖼️ UPDATE PHOTOS: New photo " . ($index + 1) . " saved to: " . $photoPath);

                            // Insert photo record into renterequipmentphotos table
                            error_log("🖼️ UPDATE PHOTOS: Inserting new photo record into renterequipmentphotos table...");
                            $photoStmt = $this->pdo->prepare("
                                INSERT INTO renterequipmentphotos (
                                    renter_equipment_id, photo_path
                                ) VALUES (?, ?)
                            ");
                            $photoStmt->execute([$equipmentId, $photoPath]);

                            error_log("🖼️ UPDATE PHOTOS: New photo record inserted successfully - ID: " . $this->pdo->lastInsertId());
                        } else {
                            error_log("🖼️ UPDATE PHOTOS: New photo " . ($index + 1) . " failed to save to file system");
                        }
                    } else {
                        error_log("🖼️ UPDATE PHOTOS: New photo " . ($index + 1) . " upload error: " . $photo['error']);
                    }
                }
            }

            // Build update query dynamically
            $updateFields = [];
            $updateValues = [];

            if ($itemCondition !== null) {
                $updateFields[] = "item_condition = ?";
                $updateValues[] = $itemCondition;
            }

            if ($pricePerDay !== null && is_numeric($pricePerDay)) {
                $updateFields[] = "price_per_day = ?";
                $updateValues[] = $pricePerDay;
            }

            if ($stockQuantity !== null && is_numeric($stockQuantity)) {
                $updateFields[] = "stock_quantity = ?";
                $updateValues[] = $stockQuantity;
            }

            if ($status !== null && in_array($status, ['Active', 'Archived'])) {
                $updateFields[] = "status = ?";
                $updateValues[] = $status;
            }

            if (empty($updateFields)) {
                $response->json([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ], 400);
                return;
            }

            $updateValues[] = $equipmentId;

            $updateStmt = $this->pdo->prepare("
                UPDATE renterequipment 
                SET " . implode(', ', $updateFields) . "
                WHERE renter_equipment_id = ?
            ");
            $updateStmt->execute($updateValues);

            $response->json([
                'success' => true,
                'message' => 'Equipment updated successfully'
            ], 200);
        } catch (Exception $e) {
            $this->log("Error updating equipment with photos: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to update equipment'
            ], 500);
        }
    }

    /**
     * Set primary photo for equipment
     * PUT /api/renter/equipment/photo/:photoId/set-primary
     */
    public function setPrimaryPhoto(Request $request, Response $response): void
    {
        error_log("⭐ SET PRIMARY: setPrimaryPhoto method called at " . date('Y-m-d H:i:s'));
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                error_log("⭐ SET PRIMARY: Authentication failed");
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }
            $renterId = $this->getRenterIdByUserId($userId);

            $photoId = $request->get('photoId'); // URL parameter
            error_log("⭐ SET PRIMARY: Attempting to set photo ID: " . $photoId . " as primary");

            // Validate photo ownership through equipment ownership
            $ownershipStmt = $this->pdo->prepare("
                SELECT rep.photo_id, rep.renter_equipment_id, re.renter_id
                FROM renterequipmentphotos rep
                JOIN renterequipment re ON rep.renter_equipment_id = re.renter_equipment_id
                WHERE rep.photo_id = ? AND re.renter_id = ?
            ");
            $ownershipStmt->execute([$photoId, $renterId]);
            $photo = $ownershipStmt->fetch(PDO::FETCH_ASSOC);

            if (!$photo) {
                error_log("⭐ SET PRIMARY: Photo not found or not owned by user");
                $response->json([
                    'success' => false,
                    'message' => 'Photo not found'
                ], 404);
                return;
            }

            $equipmentId = $photo['renter_equipment_id'];

            // Start transaction to ensure data consistency
            $this->pdo->beginTransaction();

            try {
                // First, set all photos for this equipment to order > 1 (non-primary)
                $updateAllStmt = $this->pdo->prepare("
                    UPDATE renterequipmentphotos 
                    SET display_order = display_order + 1
                    WHERE renter_equipment_id = ?
                ");
                $updateAllStmt->execute([$equipmentId]);

                // Then set the selected photo to order 1 (primary)
                $setPrimaryStmt = $this->pdo->prepare("
                    UPDATE renterequipmentphotos 
                    SET display_order = 1
                    WHERE photo_id = ?
                ");
                $setPrimaryStmt->execute([$photoId]);

                // Commit the transaction
                $this->pdo->commit();

                error_log("⭐ SET PRIMARY: Photo set as primary successfully");

                $response->json([
                    'success' => true,
                    'message' => 'Primary photo updated successfully'
                ]);
            } catch (Exception $e) {
                // Rollback on error
                $this->pdo->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("⭐ SET PRIMARY: Error setting primary photo: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to set primary photo'
            ], 500);
        }
    }

    /**
     * Get all available locations (camping and stargazing)
     * GET /api/renter/locations/available
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
            $this->log("Error fetching available locations: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch locations'
            ], 500);
        }
    }

    /**
     * Get renter's current location coverage
     * GET /api/renter/locations/coverage
     */
    public function getRenterLocations(Request $request, Response $response): void
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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

            $renterId = $this->getRenterIdByUserId($userId);

            // Get renter's current locations
            $stmt = $this->pdo->prepare("
                SELECT camping_destinations, stargazing_spots
                FROM renters 
                WHERE renter_id = ?
            ");
            $stmt->execute([$renterId]);
            $renter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$renter) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter not found'
                ], 404);
                return;
            }

            // Parse comma-separated location names into arrays
            $campingDestinations = !empty($renter['camping_destinations'])
                ? array_map('trim', explode(',', $renter['camping_destinations']))
                : [];
            $stargazingSpots = !empty($renter['stargazing_spots'])
                ? array_map('trim', explode(',', $renter['stargazing_spots']))
                : [];

            // Get full location details for current locations
            $currentCampingDetails = [];
            $currentStargazingDetails = [];

            if (!empty($campingDestinations)) {
                $placeholders = str_repeat('?,', count($campingDestinations) - 1) . '?';
                $campingDetailsStmt = $this->pdo->prepare("
                    SELECT location_id, name, type, district, description, latitude, longitude
                    FROM locations 
                    WHERE name IN ($placeholders) AND type = 'Camping'
                ");
                $campingDetailsStmt->execute($campingDestinations);
                $currentCampingDetails = $campingDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (!empty($stargazingSpots)) {
                $placeholders = str_repeat('?,', count($stargazingSpots) - 1) . '?';
                $stargazingDetailsStmt = $this->pdo->prepare("
                    SELECT location_id, name, type, district, description, latitude, longitude
                    FROM locations 
                    WHERE name IN ($placeholders) AND type = 'Stargazing'
                ");
                $stargazingDetailsStmt->execute($stargazingSpots);
                $currentStargazingDetails = $stargazingDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $response->json([
                'success' => true,
                'data' => [
                    'camping_destinations' => $currentCampingDetails,
                    'stargazing_spots' => $currentStargazingDetails,
                    'raw_camping' => $campingDestinations,
                    'raw_stargazing' => $stargazingSpots
                ]
            ]);
        } catch (Exception $e) {
            $this->log("Error fetching renter locations: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch locations'
            ], 500);
        }
    }

    /**
     * Update renter's location coverage
     * PUT /api/renter/locations/update
     */
    public function updateRenterLocations(Request $request, Response $response): void
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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

            $renterId = $this->getRenterIdByUserId($userId);

            // Get form data
            $data = $request->json() ?? [];
            $campingDestinations = $data['camping_destinations'] ?? [];
            $stargazingSpots = $data['stargazing_spots'] ?? [];

            // Validate that all provided locations exist
            if (!empty($campingDestinations)) {
                $placeholders = str_repeat('?,', count($campingDestinations) - 1) . '?';
                $validateStmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count FROM locations 
                    WHERE name IN ($placeholders) AND type = 'Camping'
                ");
                $validateStmt->execute($campingDestinations);
                $campingCount = $validateStmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($campingCount !== count($campingDestinations)) {
                    $response->json([
                        'success' => false,
                        'message' => 'One or more camping destinations are invalid'
                    ], 400);
                    return;
                }
            }

            if (!empty($stargazingSpots)) {
                $placeholders = str_repeat('?,', count($stargazingSpots) - 1) . '?';
                $validateStmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count FROM locations 
                    WHERE name IN ($placeholders) AND type = 'Stargazing'
                ");
                $validateStmt->execute($stargazingSpots);
                $stargazingCount = $validateStmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($stargazingCount !== count($stargazingSpots)) {
                    $response->json([
                        'success' => false,
                        'message' => 'One or more stargazing spots are invalid'
                    ], 400);
                    return;
                }
            }

            // Convert arrays to comma-separated strings
            $campingString = !empty($campingDestinations) ? implode(',', $campingDestinations) : null;
            $stargazingString = !empty($stargazingSpots) ? implode(',', $stargazingSpots) : null;

            // Update renter's locations
            $updateStmt = $this->pdo->prepare("
                UPDATE renters 
                SET camping_destinations = ?, stargazing_spots = ?
                WHERE renter_id = ?
            ");
            $updateStmt->execute([$campingString, $stargazingString, $renterId]);

            $response->json([
                'success' => true,
                'message' => 'Location coverage updated successfully'
            ]);
        } catch (Exception $e) {
            $this->log("Error updating renter locations: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to update locations'
            ], 500);
        }
    }

    /**
     * Get renter's bookings (past and pending)
     * GET /api/renter/bookings
     */
    public function getRenterBookings(Request $request, Response $response): void
    {
        try {
            // Get user ID from session or headers
            $userId = $this->session->get('user_id');
            if (!$userId) {
                $headers = getallheaders();
                $userId = $headers['X-User-ID'] ?? null;
                $userRole = $headers['X-User-Role'] ?? null;

                if (!$userId || $userRole !== 'service_provider') {
                    $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
                    return;
                }
            }

            $renterId = $this->getRenterIdByUserId($userId);
            if (!$renterId) {
                $response->json(['success' => false, 'message' => 'Renter not found'], 404);
                return;
            }

            // Get past/completed bookings
            $pastBookingsStmt = $this->pdo->prepare("
                SELECT 
                    b.booking_id,
                    b.booking_date,
                    b.start_date,
                    b.end_date,
                    b.total_amount,
                    b.advance_paid,
                    b.status,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    c.phone_number as customer_phone,
                    GROUP_CONCAT(
                        CONCAT(
                            e.name, 
                            ' (', bi.quantity, 'x) - Rs.', 
                            bi.price_per_day, 
                            '/day'
                        ) 
                        SEPARATOR ', '
                    ) as equipment_details
                FROM bookings b
                JOIN customers c ON b.customer_id = c.customer_id
                JOIN bookingitems bi ON b.booking_id = bi.booking_id
                JOIN renterequipment re ON bi.renter_equipment_id = re.renter_equipment_id
                JOIN equipment e ON re.equipment_id = e.equipment_id
                WHERE b.renter_id = ? 
                AND b.status = 'Completed'
                AND b.booking_type = 'Equipment'
                GROUP BY b.booking_id
                ORDER BY b.booking_date DESC
            ");
            $pastBookingsStmt->execute([$renterId]);
            $pastBookings = $pastBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get pending bookings (Confirmed status)
            $pendingBookingsStmt = $this->pdo->prepare("
                SELECT 
                    b.booking_id,
                    b.booking_date,
                    b.start_date,
                    b.end_date,
                    b.total_amount,
                    b.advance_paid,
                    b.status,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    c.phone_number as customer_phone,
                    GROUP_CONCAT(
                        CONCAT(
                            e.name, 
                            ' (', bi.quantity, 'x) - Rs.', 
                            bi.price_per_day, 
                            '/day'
                        ) 
                        SEPARATOR ', '
                    ) as equipment_details
                FROM bookings b
                JOIN customers c ON b.customer_id = c.customer_id
                JOIN bookingitems bi ON b.booking_id = bi.booking_id
                JOIN renterequipment re ON bi.renter_equipment_id = re.renter_equipment_id
                JOIN equipment e ON re.equipment_id = e.equipment_id
                WHERE b.renter_id = ? 
                AND b.status = 'Confirmed'
                AND b.booking_type = 'Equipment'
                GROUP BY b.booking_id
                ORDER BY b.booking_date DESC
            ");
            $pendingBookingsStmt->execute([$renterId]);
            $pendingBookings = $pendingBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => [
                    'past_bookings' => $pastBookings,
                    'pending_bookings' => $pendingBookings
                ]
            ]);
        } catch (Exception $e) {
            $this->log("Error fetching renter bookings: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch bookings'
            ], 500);
        }
    }

    /**
     * Mark booking as received/completed
     * PUT /api/renter/bookings/:id/mark-received
     */
    public function markBookingAsReceived(Request $request, Response $response): void
    {
        try {
            // Get user ID from session or headers
            $userId = $this->session->get('user_id');
            if (!$userId) {
                $headers = getallheaders();
                $userId = $headers['X-User-ID'] ?? null;
                $userRole = $headers['X-User-Role'] ?? null;

                if (!$userId || $userRole !== 'service_provider') {
                    $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
                    return;
                }
            }

            $renterId = $this->getRenterIdByUserId($userId);
            if (!$renterId) {
                $response->json(['success' => false, 'message' => 'Renter not found'], 404);
                return;
            }

            $bookingId = $request->get('id');
            if (!$bookingId) {
                $response->json(['success' => false, 'message' => 'Booking ID is required'], 400);
                return;
            }

            // Start transaction
            $this->pdo->beginTransaction();

            try {
                // Verify booking belongs to this renter and is in confirmed status
                $bookingStmt = $this->pdo->prepare("
                    SELECT booking_id, status 
                    FROM bookings 
                    WHERE booking_id = ? AND renter_id = ? AND status = 'Confirmed'
                ");
                $bookingStmt->execute([$bookingId, $renterId]);
                $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

                if (!$booking) {
                    $this->pdo->rollback();
                    $response->json(['success' => false, 'message' => 'Booking not found or not in confirmed status'], 404);
                    return;
                }

                // Get booking details for notification
                $bookingDetailsStmt = $this->pdo->prepare("
                    SELECT 
                        c.first_name, c.last_name,
                        e.name as equipment_name
                    FROM bookings b
                    JOIN customers c ON b.customer_id = c.customer_id
                    JOIN bookingitems bi ON b.booking_id = bi.booking_id
                    JOIN renterequipment re ON bi.renter_equipment_id = re.renter_equipment_id
                    JOIN equipment e ON re.equipment_id = e.equipment_id
                    WHERE b.booking_id = ?
                    LIMIT 1
                ");
                $bookingDetailsStmt->execute([$bookingId]);
                $bookingDetails = $bookingDetailsStmt->fetch(PDO::FETCH_ASSOC);

                // Update booking status to completed
                $updateBookingStmt = $this->pdo->prepare("
                    UPDATE bookings 
                    SET status = 'Completed', last_status_updated_by = 'Renter'
                    WHERE booking_id = ?
                ");
                $updateBookingStmt->execute([$bookingId]);

                // Update stock quantities for each item in the booking
                $bookingItemsStmt = $this->pdo->prepare("
                    SELECT bi.renter_equipment_id, bi.quantity
                    FROM bookingitems bi
                    WHERE bi.booking_id = ?
                ");
                $bookingItemsStmt->execute([$bookingId]);
                $bookingItems = $bookingItemsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($bookingItems as $item) {
                    $updateStockStmt = $this->pdo->prepare("
                        UPDATE renterequipment 
                        SET stock_quantity = stock_quantity + ?
                        WHERE renter_equipment_id = ?
                    ");
                    $updateStockStmt->execute([$item['quantity'], $item['renter_equipment_id']]);
                }

                $this->pdo->commit();

                // Send notification to renter about booking completion
                if ($bookingDetails) {
                    $this->notificationService->sendBookingCompletedNotification($userId, [
                        'customer_name' => $bookingDetails['first_name'] . ' ' . $bookingDetails['last_name'],
                        'equipment_name' => $bookingDetails['equipment_name'],
                        'booking_id' => $bookingId
                    ]);
                }

                $response->json([
                    'success' => true,
                    'message' => 'Booking marked as received successfully'
                ]);
            } catch (Exception $e) {
                $this->pdo->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            $this->log("Error marking booking as received: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to mark booking as received'
            ], 500);
        }
    }

    /**
     * Create test notifications for renter
     * POST /api/renter/test-notifications
     */
    public function createTestNotifications(Request $request, Response $response): void
    {
        try {
            // Get user ID from session or headers
            $userId = $this->session->get('user_id');
            if (!$userId) {
                $headers = getallheaders();
                $userId = $headers['X-User-ID'] ?? null;
                $userRole = $headers['X-User-Role'] ?? null;

                if (!$userId || $userRole !== 'service_provider') {
                    $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
                    return;
                }
            }

            // Create sample notifications
            $notifications = [
                [
                    'type' => 'EquipmentAddedToCart',
                    'message' => '🛒 John Doe added 2x Camping Tent to their cart. Quantity reserved until checkout.',
                ],
                [
                    'type' => 'BookingCreated',
                    'message' => '📋 New booking #12345 from Jane Smith for Sleeping Bag. Total: Rs. 5000',
                ],
                [
                    'type' => 'BookingCompleted',
                    'message' => '✅ Booking #12344 completed! Mike Johnson has marked Camping Chair as received.',
                ],
                [
                    'type' => 'Verification',
                    'message' => '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.',
                ]
            ];

            foreach ($notifications as $notification) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (user_id, type, message, is_read, created_at) 
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $notification['type'],
                    $notification['message']
                ]);
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
            $this->log("Error creating test notifications: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to create test notifications'
            ], 500);
        }
    }

    /**
     * Check if location can be removed (no active bookings)
     * GET /api/renter/locations/check-removal/:locationName
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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

            $renterId = $this->getRenterIdByUserId($userId);
            $locationName = $request->get('locationName');

            // Check for active bookings that include this location
            // For now, we'll do a simple check for any active bookings for this renter
            // In a real system, you'd need to check if any active bookings reference this location
            $bookingStmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM bookings b
                JOIN bookingitems bi ON b.booking_id = bi.booking_id
                JOIN renterequipment re ON bi.renter_equipment_id = re.renter_equipment_id
                WHERE re.renter_id = ? 
                AND b.status IN ('Pending', 'Confirmed', 'In Progress')
            ");
            $bookingStmt->execute([$renterId]);
            $bookingCount = $bookingStmt->fetch(PDO::FETCH_ASSOC)['count'];

            // For now, allow removal if no active bookings exist
            // TODO: Implement proper location-specific booking check
            $canRemove = $bookingCount === 0;

            $response->json([
                'success' => true,
                'data' => [
                    'can_remove' => $canRemove,
                    'active_bookings' => $bookingCount,
                    'message' => $canRemove
                        ? 'Location can be safely removed'
                        : "Cannot remove location. There are {$bookingCount} active booking(s) that may reference this location."
                ]
            ]);
        } catch (Exception $e) {
            $this->log("Error checking location removal: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to check location removal'
            ], 500);
        }
    }

    /**
     * Remove specific equipment photo
     * DELETE /api/renter/equipment/photo/:photoId
     */
    public function removeEquipmentPhoto(Request $request, Response $response): void
    {
        error_log("🖼️ REMOVE PHOTO: removeEquipmentPhoto method called at " . date('Y-m-d H:i:s'));
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                error_log("🖼️ REMOVE PHOTO: Authentication failed");
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }
            $renterId = $this->getRenterIdByUserId($userId);

            $photoId = $request->get('photoId'); // URL parameter
            error_log("🖼️ REMOVE PHOTO: Attempting to remove photo ID: " . $photoId);

            // Validate photo ownership through equipment ownership
            $ownershipStmt = $this->pdo->prepare("
                SELECT rep.photo_id, rep.photo_path, rep.renter_equipment_id 
                FROM renterequipmentphotos rep
                JOIN renterequipment re ON rep.renter_equipment_id = re.renter_equipment_id
                WHERE rep.photo_id = ? AND re.renter_id = ?
            ");
            $ownershipStmt->execute([$photoId, $renterId]);
            $photo = $ownershipStmt->fetch(PDO::FETCH_ASSOC);

            if (!$photo) {
                error_log("🖼️ REMOVE PHOTO: Photo not found or not owned by user");
                $response->json([
                    'success' => false,
                    'message' => 'Photo not found'
                ], 404);
                return;
            }

            // Delete photo file from filesystem
            $fileService = new FileService();
            $storagePath = $fileService->getStoragePath();
            $fullPath = $storagePath . '/' . $photo['photo_path'];

            if (file_exists($fullPath)) {
                if (unlink($fullPath)) {
                    error_log("🖼️ REMOVE PHOTO: Photo file deleted from filesystem: " . $fullPath);
                } else {
                    error_log("🖼️ REMOVE PHOTO: Failed to delete photo file: " . $fullPath);
                }
            } else {
                error_log("🖼️ REMOVE PHOTO: Photo file not found in filesystem: " . $fullPath);
            }

            // Delete photo record from database
            $deleteStmt = $this->pdo->prepare("
                DELETE FROM renterequipmentphotos 
                WHERE photo_id = ?
            ");
            $deleteStmt->execute([$photoId]);

            error_log("🖼️ REMOVE PHOTO: Photo record deleted from database");

            $response->json([
                'success' => true,
                'message' => 'Photo removed successfully'
            ]);
        } catch (Exception $e) {
            error_log("🖼️ REMOVE PHOTO: Error removing photo: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to remove photo'
            ], 500);
        }
    }

    /**
     * Restore archived equipment (make it active again)
     * PUT /api/renter/equipment/restore/:id
     */
    public function restoreEquipment(Request $request, Response $response): void
    {
        error_log("🔄 RESTORE: restoreEquipment method called at " . date('Y-m-d H:i:s'));
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                error_log("🔄 RESTORE: Authentication failed");
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
                return;
            }
            $renterId = $this->getRenterIdByUserId($userId);

            $equipmentId = $request->get('id'); // URL parameter
            error_log("🔄 RESTORE: Attempting to restore equipment ID: " . $equipmentId);

            // Validate equipment ownership and check if it's archived
            $ownershipStmt = $this->pdo->prepare("
                SELECT renter_equipment_id, status FROM renterequipment 
                WHERE renter_equipment_id = ? AND renter_id = ?
            ");
            $ownershipStmt->execute([$equipmentId, $renterId]);
            $equipment = $ownershipStmt->fetch(PDO::FETCH_ASSOC);

            if (!$equipment) {
                error_log("🔄 RESTORE: Equipment not found or not owned by user");
                $response->json([
                    'success' => false,
                    'message' => 'Equipment not found'
                ], 404);
                return;
            }

            if ($equipment['status'] !== 'Archived') {
                error_log("🔄 RESTORE: Equipment is not archived (status: " . $equipment['status'] . ")");
                $response->json([
                    'success' => false,
                    'message' => 'Equipment is not archived'
                ], 400);
                return;
            }

            // Restore equipment (set status to Active)
            $restoreStmt = $this->pdo->prepare("
                UPDATE renterequipment 
                SET status = 'Active' 
                WHERE renter_equipment_id = ?
            ");
            $restoreStmt->execute([$equipmentId]);

            error_log("🔄 RESTORE: Equipment restored successfully");

            $response->json([
                'success' => true,
                'message' => 'Equipment restored successfully'
            ]);
        } catch (Exception $e) {
            error_log("🔄 RESTORE: Error restoring equipment: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to restore equipment'
            ], 500);
        }
    }

    /**
     * Delete equipment (archive it)
     * DELETE /api/renter/equipment/delete/{id}
     */
    public function deleteEquipment(Request $request, Response $response): void
    {
        error_log("🗑️ DELETE: deleteEquipment method called at " . date('Y-m-d H:i:s'));
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
                        SELECT user_id, role FROM users WHERE user_id = ? AND role = 'Renter'
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
            $renterId = $this->getRenterIdByUserId($userId);

            $equipmentId = $request->get('id'); // URL parameter
            error_log("🗑️ DELETE: Attempting to delete equipment ID: " . $equipmentId);

            // Validate equipment ownership
            $ownershipStmt = $this->pdo->prepare("
                SELECT renter_equipment_id FROM renterequipment 
                WHERE renter_equipment_id = ? AND renter_id = ?
            ");
            $ownershipStmt->execute([$equipmentId, $renterId]);

            if (!$ownershipStmt->fetch()) {
                $response->json([
                    'success' => false,
                    'message' => 'Equipment not found or access denied'
                ], 404);
                return;
            }

            // Archive equipment (set status to Archived)
            $updateStmt = $this->pdo->prepare("
                UPDATE renterequipment 
                SET status = 'Archived'
                WHERE renter_equipment_id = ?
            ");
            $updateStmt->execute([$equipmentId]);

            $response->json([
                'success' => true,
                'message' => 'Equipment archived successfully'
            ], 200);
        } catch (Exception $e) {
            $this->log("Error deleting equipment: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to delete equipment'
            ], 500);
        }
    }
}
