<?php

/**
 * Guide Controller
 * Handles guide-related API endpoints
 */

class GuideController extends Controller
{
    private GuideRepository $guideRepository;

    public function __construct()
    {
        parent::__construct();
        $this->guideRepository = new GuideRepository();
    }

    /**
     * Get all guides with their profile information
     */
    public function list(Request $request, Response $response): void
    {
        try {
            // Get all guides from database
            $guides = $this->getAllGuides();

            $response->json([
                'success' => true,
                'data' => $guides
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching guides: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch guides'
            ], 500);
        }
    }

    /**
     * Get all guides with formatted data for frontend
     */
    private function getAllGuides(): array
    {
        $pdo = Database::getConnection();

        // Join guides with users table to get email and role
        $sql = "SELECT 
                    g.guide_id,
                    g.user_id,
                    g.first_name,
                    g.last_name,
                    g.phone_number,
                    g.profile_picture,
                    g.district,
                    g.description,
                    g.special_note,
                    g.currency,
                    g.languages,
                    g.price_per_day,
                    g.verification_status,
                    g.created_at,
                    u.email,
                    u.role
                FROM guides g
                JOIN users u ON g.user_id = u.user_id
                WHERE u.role = 'Guide'
                ORDER BY g.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for frontend
        $formattedGuides = [];
        foreach ($guides as $guide) {
            // Generate full name
            $fullName = trim($guide['first_name'] . ' ' . $guide['last_name']);

            // Format phone number
            $phoneNumber = $guide['phone_number'];
            if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                // Convert 0xxxxxxxxx to +94 xxxxxxxxx
                $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
            }

            // Handle profile picture URL
            $profileImage = null;
            if ($guide['profile_picture']) {
                // Use same pattern as LocationController for consistency
                $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $guide['profile_picture'];
            }

            // Format price
            $price = $guide['price_per_day'] ? number_format($guide['price_per_day'], 0) : '0';

            // Parse languages
            $languages = [];
            if ($guide['languages']) {
                $languages = array_map('trim', explode(',', $guide['languages']));
            }

            $formattedGuides[] = [
                'id' => $guide['guide_id'],
                'userId' => $guide['user_id'],
                'name' => $fullName,
                'location' => $guide['district'] ?? 'Unknown',
                'phone' => $phoneNumber,
                'email' => $guide['email'],
                'image' => $profileImage,
                'rating' => 4.8, // Default rating - can be calculated from reviews later
                'reviewCount' => 12, // Default review count - can be calculated from reviews later
                'rate' => $price,
                'currency' => $guide['currency'] ?? 'LKR',
                'languages' => $languages,
                'description' => $guide['description'],
                'specialNote' => $guide['special_note'],
                'verificationStatus' => $guide['verification_status'],
                'createdAt' => $guide['created_at']
            ];
        }

        return $formattedGuides;
    }

    /**
     * Get a specific guide by ID
     */
    public function show(Request $request, Response $response): void
    {
        try {
            $guideId = $request->get('id');

            if (!$guideId) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide ID is required'
                ], 400);
                return;
            }

            $guide = $this->guideRepository->findById($guideId);

            if (!$guide) {
                $response->json([
                    'success' => false,
                    'message' => 'Guide not found'
                ], 404);
                return;
            }

            // Get user information
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT email, role FROM users WHERE user_id = ?");
            $stmt->execute([$guide->userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['role'] !== 'Guide') {
                $response->json([
                    'success' => false,
                    'message' => 'Guide not found'
                ], 404);
                return;
            }

            // Format the data
            $fullName = $guide->getFullName();
            $phoneNumber = $guide->phoneNumber;
            if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
            }

            $profileImage = null;
            if ($guide->profilePicture) {
                // Use same pattern as LocationController for consistency
                $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $guide->profilePicture;
            }

            // Format price
            $price = $guide->pricePerDay ? number_format($guide->pricePerDay, 0) : '0';

            // Parse languages
            $languages = [];
            if ($guide->languages) {
                $languages = array_map('trim', explode(',', $guide->languages));
            }

            $formattedGuide = [
                'id' => $guide->guideId,
                'userId' => $guide->userId,
                'name' => $fullName,
                'location' => $guide->district ?? 'Unknown',
                'phone' => $phoneNumber,
                'email' => $user['email'],
                'image' => $profileImage,
                'rating' => 4.8,
                'reviewCount' => 12,
                'rate' => $price,
                'currency' => $guide->currency ?? 'LKR',
                'languages' => $languages,
                'description' => $guide->description,
                'specialNote' => $guide->specialNote,
                'verificationStatus' => $guide->verificationStatus,
                'createdAt' => $guide->createdAt,
                'details' => [
                    'dob' => $guide->dob,
                    'gender' => $guide->gender,
                    'homeAddress' => $guide->homeAddress,
                    'nicNumber' => $guide->nicNumber,
                    'campingDestinations' => $guide->campingDestinations,
                    'stargazingSpots' => $guide->stargazingSpots,
                    'latitude' => $guide->latitude,
                    'longitude' => $guide->longitude
                ]
            ];

            $response->json([
                'success' => true,
                'data' => $formattedGuide
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching guide: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch guide'
            ], 500);
        }
    }

    /**
     * Get guides filtered by district
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

            // Join guides with users table to get email and role, filtered by district
            $sql = "SELECT 
                        g.guide_id,
                        g.user_id,
                        g.first_name,
                        g.last_name,
                        g.phone_number,
                        g.profile_picture,
                        g.district,
                        g.description,
                        g.special_note,
                        g.currency,
                        g.languages,
                        g.price_per_day,
                        g.verification_status,
                        g.created_at,
                        u.email,
                        u.role
                    FROM guides g
                    JOIN users u ON g.user_id = u.user_id
                    WHERE u.role = 'Guide' AND g.district = ?
                    ORDER BY g.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$district]);

            $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the data for frontend (same logic as getAllGuides)
            $formattedGuides = [];
            foreach ($guides as $guide) {
                // Generate full name
                $fullName = trim($guide['first_name'] . ' ' . $guide['last_name']);

                // Format phone number
                $phoneNumber = $guide['phone_number'];
                if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
                    // Convert 0xxxxxxxxx to +94 xxxxxxxxx
                    $phoneNumber = '+94 ' . substr($phoneNumber, 1, 2) . ' ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6);
                }

                // Handle profile picture URL
                $profileImage = null;
                if ($guide['profile_picture']) {
                    // Use same pattern as LocationController for consistency
                    $profileImage = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $guide['profile_picture'];
                }

                // Format price
                $price = $guide['price_per_day'] ? number_format($guide['price_per_day'], 0) : '0';

                // Parse languages
                $languages = [];
                if ($guide['languages']) {
                    $languages = array_map('trim', explode(',', $guide['languages']));
                }

                $formattedGuides[] = [
                    'id' => $guide['guide_id'],
                    'userId' => $guide['user_id'],
                    'name' => $fullName,
                    'location' => $guide['district'] ?? 'Unknown',
                    'phone' => $phoneNumber,
                    'email' => $guide['email'],
                    'image' => $profileImage,
                    'rating' => 4.8, // Default rating - can be calculated from reviews later
                    'reviewCount' => 12, // Default review count - can be calculated from reviews later
                    'rate' => $price,
                    'currency' => $guide['currency'] ?? 'LKR',
                    'languages' => $languages,
                    'description' => $guide['description'],
                    'specialNote' => $guide['special_note'],
                    'verificationStatus' => $guide['verification_status'],
                    'createdAt' => $guide['created_at']
                ];
            }

            $response->json([
                'success' => true,
                'data' => $formattedGuides
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching guides by district: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch guides by district'
            ], 500);
        }
    }
}
