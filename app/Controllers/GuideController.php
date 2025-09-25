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

            // Get availability data
            $availability = $this->getGuideAvailability($pdo, $guide->guideId);

            // Get gallery images
            $galleryImages = $this->getGuideGalleryImages($pdo, $guide->guideId);

            // Get ratings and reviews
            $ratingData = $this->getGuideRatings($pdo, $guide->guideId);

            $formattedGuide = [
                'id' => $guide->guideId,
                'userId' => $guide->userId,
                'name' => $fullName,
                'location' => $guide->district ?? 'Unknown',
                'phone' => $phoneNumber,
                'email' => $user['email'],
                'image' => $profileImage,
                'rating' => $ratingData['averageRating'],
                'reviewCount' => $ratingData['reviewCount'],
                'rate' => $price,
                'currency' => $guide->currency ?? 'LKR',
                'languages' => $languages,
                'description' => $guide->description,
                'specialNote' => $guide->specialNote,
                'verificationStatus' => $guide->verificationStatus,
                'createdAt' => $guide->createdAt,
                'availability' => $availability,
                'galleryImages' => $galleryImages,
                'reviews' => $ratingData['reviews'],
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

    /**
     * Get guide availability data
     */
    private function getGuideAvailability(PDO $pdo, int $guideId): array
    {
        try {
            $sql = "SELECT day_of_week, start_time, end_time 
                    FROM guideavailability 
                    WHERE guide_id = ? 
                    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$guideId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create availability array for all days
            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $availability = [];

            foreach ($daysOfWeek as $day) {
                $dayAvailability = array_filter($results, function ($row) use ($day) {
                    return $row['day_of_week'] === $day;
                });

                if (!empty($dayAvailability)) {
                    // Format time ranges
                    $timeRanges = [];
                    foreach ($dayAvailability as $slot) {
                        $startTime = date('g:i A', strtotime($slot['start_time']));
                        $endTime = date('g:i A', strtotime($slot['end_time']));
                        $timeRanges[] = $startTime . ' - ' . $endTime;
                    }
                    $availability[] = [
                        'day' => $day,
                        'available' => true,
                        'time' => implode(', ', $timeRanges)
                    ];
                } else {
                    $availability[] = [
                        'day' => $day,
                        'available' => false,
                        'time' => ''
                    ];
                }
            }

            return $availability;
        } catch (Exception $e) {
            error_log("Error fetching guide availability: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get guide gallery images
     */
    private function getGuideGalleryImages(PDO $pdo, int $guideId): array
    {
        try {
            $sql = "SELECT image_path, uploaded_at 
                    FROM guideimages 
                    WHERE guide_id = ? 
                    ORDER BY uploaded_at ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$guideId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $images = [];
            foreach ($results as $row) {
                $images[] = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $row['image_path'];
            }

            return $images;
        } catch (Exception $e) {
            error_log("Error fetching guide gallery images: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get guide ratings and reviews
     */
    private function getGuideRatings(PDO $pdo, int $guideId): array
    {
        // Get ratings from the ratings table
        $sql = "SELECT 
                    AVG(r.rating) as average_rating,
                    COUNT(r.rating) as review_count
                FROM ratings r 
                WHERE r.entity_type = 'Guide' 
                AND r.entity_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$guideId]);
        $ratingStats = $stmt->fetch();

        $averageRating = $ratingStats['average_rating'] ? (float) $ratingStats['average_rating'] : 0.0;
        $reviewCount = (int) $ratingStats['review_count'];

        // For now, return sample reviews since we don't have a reviews table
        // In a real application, you would have a separate reviews table
        $sampleReviews = [
            [
                'rating' => 5,
                'text' => 'Excellent guide! Very knowledgeable about the local area and made our camping trip unforgettable.',
                'name' => 'Sarah Johnson',
                'date' => '2 weeks ago'
            ],
            [
                'rating' => 4,
                'text' => 'Great experience overall. The guide was professional and helpful throughout the trip.',
                'name' => 'Mike Chen',
                'date' => '1 month ago'
            ],
            [
                'rating' => 5,
                'text' => 'Amazing stargazing experience! The guide knew all the best spots and was very patient with our group.',
                'name' => 'Emily Rodriguez',
                'date' => '1 month ago'
            ]
        ];

        return [
            'averageRating' => $averageRating > 0 ? $averageRating : 4.8, // Default rating if no reviews
            'reviewCount' => $reviewCount > 0 ? $reviewCount : 12, // Default count if no reviews
            'reviews' => $sampleReviews
        ];
    }
}
