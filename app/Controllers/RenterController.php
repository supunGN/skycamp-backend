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
     * Get renters who have ALL of the selected equipment items
     * Query param: equipment_ids (comma-separated list of equipment_id)
     * Only considers active renter equipment (renterequipment.status = 'Active')
     */
    public function getByEquipment(Request $request, Response $response): void
    {
        try {
            // Parse equipment_ids from query string
            $raw = $request->get('equipment_ids');
            if (!$raw) {
                // No selection -> return all renters (same as default behavior)
                $renters = $this->getAllRenters();
                $response->json([
                    'success' => true,
                    'data' => $renters
                ], 200);
                return;
            }

            // Normalize into array of unique integer IDs
            $ids = array_filter(array_unique(array_map(function ($v) {
                return (int)preg_replace('/[^0-9]/', '', trim($v));
            }, explode(',', (string)$raw))), function ($v) {
                return $v > 0;
            });

            if (empty($ids)) {
                $renters = $this->getAllRenters();
                $response->json([
                    'success' => true,
                    'data' => $renters
                ], 200);
                return;
            }

            $pdo = Database::getConnection();

            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Select renters who have ALL of the selected equipment items (active only)
            // Using GROUP BY and HAVING COUNT to ensure renter has all selected items
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
                    JOIN renterequipment re ON re.renter_id = r.renter_id AND re.status = 'Active'
                    WHERE u.role = 'Renter' AND re.equipment_id IN ($placeholders)
                    GROUP BY r.renter_id
                    HAVING COUNT(DISTINCT re.equipment_id) = ?
                    ORDER BY r.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $params = array_values($ids);
            $params[] = count($ids); // Add count for HAVING clause
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format like other list endpoints
            $formattedRenters = [];
            foreach ($rows as $renter) {
                $fullName = trim($renter['first_name'] . ' ' . $renter['last_name']);

                $phoneNumber = $renter['phone_number'];
                if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                    $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
                }

                $profileImage = null;
                if ($renter['profile_picture']) {
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
                    'rating' => 5.0,
                    'reviewCount' => 22,
                    'verificationStatus' => $renter['verification_status'],
                    'createdAt' => $renter['created_at']
                ];
            }

            $response->json([
                'success' => true,
                'data' => $formattedRenters
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching renters by equipment: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch renters by equipment'
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
                // Calculate available stock (total - reserved - booked)
                $availableStock = $this->calculateAvailableStock($pdo, $row['renter_equipment_id']);

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
                    'stockQuantity' => $availableStock, // Use calculated available stock
                    'isAvailable' => $availableStock > 0,
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

    /**
     * Calculate available stock (total - reserved - booked)
     */
    private function calculateAvailableStock(PDO $pdo, int $renterEquipmentId): int
    {
        try {
            // Get total stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM renterequipment WHERE renter_equipment_id = ?");
            $stmt->execute([$renterEquipmentId]);
            $totalStock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];

            // Get reserved quantity (from active carts)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(ci.quantity), 0) as reserved
                FROM cartitems ci
                JOIN carts c ON ci.cart_id = c.cart_id
                WHERE ci.renter_equipment_id = ? AND c.status = 'Active'
                AND (c.expires_at IS NULL OR c.expires_at > NOW())
            ");
            $stmt->execute([$renterEquipmentId]);
            $reserved = $stmt->fetch(PDO::FETCH_ASSOC)['reserved'];

            // Get booked quantity (from confirmed bookings)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(er.quantity), 0) as booked
                FROM equipment_reservations er
                WHERE er.renter_equipment_id = ? AND er.status = 'Booked'
            ");
            $stmt->execute([$renterEquipmentId]);
            $booked = $stmt->fetch(PDO::FETCH_ASSOC)['booked'];

            return max(0, $totalStock - $reserved - $booked);
        } catch (Exception $e) {
            error_log("Error calculating available stock: " . $e->getMessage());
            return 0;
        }
    }
}
