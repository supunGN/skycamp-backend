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

            $renter = $this->renterRepository->findById($renterId);

            if (!$renter) {
                $response->json([
                    'success' => false,
                    'message' => 'Renter not found'
                ], 404);
                return;
            }

            // Get user information
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT email, role FROM users WHERE user_id = ?");
            $stmt->execute([$renter->userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['role'] !== 'Renter') {
                $response->json([
                    'success' => false,
                    'message' => 'Renter not found'
                ], 404);
                return;
            }

            // Format the data
            $fullName = $renter->getFullName();
            $phoneNumber = $renter->phoneNumber;
            if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
            }

            $profileImage = null;
            if ($renter->profilePicture) {
                // Use same pattern as LocationController for consistency
                $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $renter->profilePicture;
            }

            $formattedRenter = [
                'id' => $renter->renterId,
                'userId' => $renter->userId,
                'name' => $fullName,
                'location' => $renter->district ?? 'Unknown',
                'phone' => $phoneNumber,
                'email' => $user['email'],
                'image' => $profileImage,
                'rating' => 5.0,
                'reviewCount' => 22,
                'verificationStatus' => $renter->verificationStatus,
                'createdAt' => $renter->createdAt,
                'details' => [
                    'dob' => $renter->dob,
                    'gender' => $renter->gender,
                    'homeAddress' => $renter->homeAddress,
                    'nicNumber' => $renter->nicNumber,
                    'campingDestinations' => $renter->campingDestinations,
                    'stargazingSpots' => $renter->stargazingSpots,
                    'latitude' => $renter->latitude,
                    'longitude' => $renter->longitude
                ]
            ];

            $response->json([
                'success' => true,
                'data' => $formattedRenter
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
}
