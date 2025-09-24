<?php

/**
 * Renter Controller
 * Handles renter-related API endpoints
 */

class RenterController extends Controller
{
    private RenterRepository $renterRepository;

    public function __construct()
    {
        parent::__construct();
        $this->renterRepository = new RenterRepository();
    }

    /**
     * Get all renters with their profile information
     */
    public function list(Request $request, Response $response): void
    {
        try {
            // Get all renters from database
            $renters = $this->getAllRenters();

            $response->json([
                'success' => true,
                'data' => $renters
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching renters: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch renters'
            ], 500);
        }
    }

    /**
     * Get all renters with formatted data for frontend
     */
    private function getAllRenters(): array
    {
        $pdo = Database::getConnection();

        // Join renters with users table to get email and role
        $sql = "SELECT 
                    r.renter_id,
                    r.user_id,
                    r.first_name,
                    r.last_name,
                    r.phone_number,
                    r.profile_picture,
                    r.district,
                    r.verification_status,
                    r.created_at,
                    u.email,
                    u.role
                FROM renters r
                JOIN users u ON r.user_id = u.user_id
                WHERE u.role = 'Renter'
                ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for frontend
        $formattedRenters = [];
        foreach ($renters as $renter) {
            // Generate full name
            $fullName = trim($renter['first_name'] . ' ' . $renter['last_name']);

            // Format phone number
            $phoneNumber = $renter['phone_number'];
            if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                // Convert 0xxxxxxxxx to +94 xxxxxxxxx
                $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
            }

            // Handle profile picture URL
            $profileImage = null;
            if ($renter['profile_picture']) {
                // Use same pattern as LocationController for consistency
                $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $renter['profile_picture'];
            }

            $formattedRenters[] = [
                'id' => $renter['renter_id'],
                'userId' => $renter['user_id'],
                'name' => $fullName,
                'location' => $renter['district'] ?? 'Unknown',
                'phone' => $phoneNumber,
                'email' => $renter['email'],
                'image' => $profileImage,
                'rating' => 5.0, // Default rating - can be calculated from reviews later
                'reviewCount' => 22, // Default review count - can be calculated from reviews later
                'verificationStatus' => $renter['verification_status'],
                'createdAt' => $renter['created_at']
            ];
        }

        return $formattedRenters;
    }

    /**
     * Get a specific renter by ID
     */
    public function show(Request $request, Response $response): void
    {
        try {
            $renterId = $request->get('id');

            if (!$renterId) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter ID is required'
                ], 400);
                return;
            }

            // Simple direct database query to get renter data
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM renters WHERE renter_id = ?");
            $stmt->execute([$renterId]);
            $renter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$renter) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter not found'
                ], 404);
                return;
            }

            // Get user email
            $userStmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
            $userStmt->execute([$renter['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            // Format phone number
            $phoneNumber = $renter['phone_number'];
            if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
            }

            // Format profile image
            $profileImage = null;
            if ($renter['profile_picture']) {
                $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $renter['profile_picture'];
            }

            // Get equipment data
            $equipmentData = $this->getRenterEquipment($pdo, $renterId);

            // Simple response with basic data and equipment
            $response->json([
                'success' => true,
                'data' => [
                    'id' => $renter['renter_id'],
                    'userId' => $renter['user_id'],
                    'name' => $renter['first_name'] . ' ' . $renter['last_name'],
                    'location' => $renter['district'] ?? 'Unknown',
                    'phone' => $phoneNumber,
                    'email' => $user['email'] ?? '',
                    'image' => $profileImage,
                    'rating' => 0.0,
                    'reviewCount' => 0,
                    'verificationStatus' => $renter['verification_status'],
                    'createdAt' => $renter['created_at'],
                    'equipment' => $equipmentData,
                    'reviews' => [],
                    'details' => [
                        'dob' => $renter['dob'],
                        'gender' => $renter['gender'],
                        'homeAddress' => $renter['home_address'],
                        'nicNumber' => $renter['nic_number'],
                        'campingDestinations' => $renter['camping_destinations'],
                        'stargazingSpots' => $renter['stargazing_spots'],
                        'latitude' => $renter['latitude'],
                        'longitude' => $renter['longitude']
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching renter: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch renter'
            ], 500);
        }
    }

    /**
     * Get renters filtered by district
     */
    public function getByDistrict(Request $request, Response $response): void
    {
        try {
            $district = $request->get('district');

            if (!$district) {
                $response->json([
                    'success' => false,
                    'message' => 'District parameter is required'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Join renters with users table to get email and role, filtered by district
            $sql = "SELECT 
                        r.renter_id,
                        r.user_id,
                        r.first_name,
                        r.last_name,
                        r.phone_number,
                        r.profile_picture,
                        r.district,
                        r.verification_status,
                        r.created_at,
                        u.email,
                        u.role
                    FROM renters r
                    JOIN users u ON r.user_id = u.user_id
                    WHERE u.role = 'Renter' AND r.district = ?
                    ORDER BY r.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$district]);

            $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the data for frontend (same logic as getAllRenters)
            $formattedRenters = [];
            foreach ($renters as $renter) {
                // Generate full name
                $fullName = trim($renter['first_name'] . ' ' . $renter['last_name']);

                // Format phone number
                $phoneNumber = $renter['phone_number'];
                if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                    // Convert 0xxxxxxxxx to +94 xxxxxxxxx
                    $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
                }

                // Handle profile picture URL
                $profileImage = null;
                if ($renter['profile_picture']) {
                    // Use same pattern as LocationController for consistency
                    $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $renter['profile_picture'];
                }

                $formattedRenters[] = [
                    'id' => $renter['renter_id'],
                    'userId' => $renter['user_id'],
                    'name' => $fullName,
                    'location' => $renter['district'] ?? 'Unknown',
                    'phone' => $phoneNumber,
                    'email' => $renter['email'],
                    'image' => $profileImage,
                    'rating' => 5.0, // Default rating - can be calculated from reviews later
                    'reviewCount' => 22, // Default review count - can be calculated from reviews later
                    'verificationStatus' => $renter['verification_status'],
                    'createdAt' => $renter['created_at']
                ];
            }

            $response->json([
                'success' => true,
                'data' => $formattedRenters
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching renters by district: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch renters by district'
            ], 500);
        }
    }

    /**
     * Get renter equipment with primary images
     */
    private function getRenterEquipment(PDO $pdo, int $renterId): array
    {
        try {
            $sql = "
                SELECT 
                    re.renter_equipment_id,
                    re.equipment_id,
                    re.item_condition,
                    re.price_per_day,
                    re.stock_quantity,
                    re.status,
                    e.name as equipment_name,
                    e.description as equipment_description,
                    ec.type as category_type,
                    ec.name as category_name,
                    rep.photo_path,
                    rep.display_order
                FROM renterequipment re
                JOIN equipment e ON re.equipment_id = e.equipment_id
                JOIN equipment_categories ec ON e.category_id = ec.category_id
                LEFT JOIN renterequipmentphotos rep ON re.renter_equipment_id = rep.renter_equipment_id 
                    AND rep.photo_id = (
                        SELECT rep2.photo_id 
                        FROM renterequipmentphotos rep2 
                        WHERE rep2.renter_equipment_id = re.renter_equipment_id
                        ORDER BY COALESCE(rep2.display_order, 999), rep2.photo_id
                        LIMIT 1
                    )
                WHERE re.renter_id = ? AND re.status = 'Active'
                ORDER BY ec.type, e.name
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$renterId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $equipment = [];
            foreach ($results as $row) {
                // Use uploaded image or default equipment image
                $equipmentImage = '/default-equipment.png'; // Default fallback
                if (!empty($row['photo_path']) && $row['photo_path'] !== null) {
                    $equipmentImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $row['photo_path'];
                }

                $equipment[] = [
                    'id' => (int)$row['renter_equipment_id'],
                    'equipmentId' => (int)$row['equipment_id'],
                    'name' => $row['equipment_name'],
                    'description' => $row['equipment_description'],
                    'condition' => $row['item_condition'],
                    'pricePerDay' => (float)$row['price_per_day'],
                    'stockQuantity' => (int)$row['stock_quantity'],
                    'isAvailable' => (int)$row['stock_quantity'] > 0,
                    'categoryType' => $row['category_type'],
                    'categoryName' => $row['category_name'],
                    'image' => $equipmentImage
                ];
            }

            return $equipment;
        } catch (Exception $e) {
            error_log("Error fetching renter equipment: " . $e->getMessage());
            return [];
        }
    }
}
