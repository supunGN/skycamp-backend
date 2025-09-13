<?php

class LocationController extends Controller
{
    private $locationRepository;

    public function __construct()
    {
        $this->locationRepository = new LocationRepository();
    }

    /**
     * Get all camping destinations
     */
    public function getCampingDestinations(Request $request, Response $response): void
    {
        try {
            $locations = $this->locationRepository->getCampingDestinations();
            $response->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching camping destinations: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch camping destinations'
            ], 500);
        }
    }

    /**
     * Get all stargazing spots
     */
    public function getStargazingSpots(Request $request, Response $response): void
    {
        try {
            $locations = $this->locationRepository->getStargazingSpots();
            $response->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching stargazing spots: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch stargazing spots'
            ], 500);
        }
    }

    /**
     * Get all locations (both camping and stargazing)
     */
    public function getAllLocations(Request $request, Response $response): void
    {
        try {
            $locations = $this->locationRepository->getAllLocations();
            $response->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching all locations: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch locations'
            ], 500);
        }
    }

    /**
     * Get locations by type
     */
    public function getLocationsByType(Request $request, Response $response): void
    {
        $type = $request->get('type');

        if (!$type || !in_array($type, ['Camping', 'Stargazing'])) {
            $response->json([
                'success' => false,
                'message' => 'Invalid type. Must be "Camping" or "Stargazing"'
            ], 400);
            return;
        }

        try {
            $locations = $this->locationRepository->getLocationsByType($type);
            $response->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching locations by type: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch locations'
            ], 500);
        }
    }

    /**
     * Get camping destinations with images for frontend display
     */
    public function getCampingDestinationsWithImages(Request $request, Response $response): void
    {
        try {
            $destinations = $this->locationRepository->getCampingDestinationsWithImages();

            // Transform image paths to full URLs
            $destinations = array_map(function ($destination) {
                if ($destination['image_path']) {
                    $destination['image_url'] = 'http://localhost/skycamp' . $destination['image_path'];
                } else {
                    $destination['image_url'] = null;
                }
                return $destination;
            }, $destinations);

            $response->json([
                'success' => true,
                'data' => $destinations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching camping destinations with images: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch camping destinations'
            ], 500);
        }
    }

    /**
     * Get stargazing spots with images for frontend display
     */
    public function getStargazingSpotsWithImages(Request $request, Response $response): void
    {
        try {
            $spots = $this->locationRepository->getStargazingSpotsWithImages();

            // Transform image paths to full URLs
            $spots = array_map(function ($spot) {
                if ($spot['image_path']) {
                    $spot['image_url'] = 'http://localhost/skycamp' . $spot['image_path'];
                } else {
                    $spot['image_url'] = null;
                }
                return $spot;
            }, $spots);

            $response->json([
                'success' => true,
                'data' => $spots
            ]);
        } catch (Exception $e) {
            error_log("Error fetching stargazing spots with images: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch stargazing spots'
            ], 500);
        }
    }

    /**
     * Get top 3 camping destinations with images for home page
     */
    public function getTopCampingDestinationsWithImages(Request $request, Response $response): void
    {
        try {
            $limit = (int) $request->get('limit', 3);
            $destinations = $this->locationRepository->getTopCampingDestinationsWithImages($limit);

            // Transform image paths to full URLs
            $destinations = array_map(function ($destination) {
                if ($destination['image_path']) {
                    $destination['image_url'] = 'http://localhost/skycamp' . $destination['image_path'];
                } else {
                    $destination['image_url'] = null;
                }
                return $destination;
            }, $destinations);

            $response->json([
                'success' => true,
                'data' => $destinations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching top camping destinations with images: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch top camping destinations'
            ], 500);
        }
    }

    /**
     * Get top 3 stargazing spots with images for home page
     */
    public function getTopStargazingSpotsWithImages(Request $request, Response $response): void
    {
        try {
            $limit = (int) $request->get('limit', 3);
            $spots = $this->locationRepository->getTopStargazingSpotsWithImages($limit);

            // Transform image paths to full URLs
            $spots = array_map(function ($spot) {
                if ($spot['image_path']) {
                    $spot['image_url'] = 'http://localhost/skycamp' . $spot['image_path'];
                } else {
                    $spot['image_url'] = null;
                }
                return $spot;
            }, $spots);

            $response->json([
                'success' => true,
                'data' => $spots
            ]);
        } catch (Exception $e) {
            error_log("Error fetching top stargazing spots with images: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch top stargazing spots'
            ], 500);
        }
    }

    /**
     * Get individual location with all details and images
     */
    public function getLocationWithImages(Request $request, Response $response): void
    {
        try {
            $locationId = (int) $request->get('id');

            if (!$locationId) {
                $response->json([
                    'success' => false,
                    'message' => 'Location ID is required'
                ], 400);
                return;
            }

            $location = $this->locationRepository->getLocationWithImages($locationId);

            if (!$location) {
                $response->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
                return;
            }

            // Transform image paths to full URLs
            if (!empty($location['images'])) {
                $location['images'] = array_map(function ($image) {
                    $image['image_url'] = 'http://localhost/skycamp' . $image['image_path'];
                    return $image;
                }, $location['images']);
            }

            $response->json([
                'success' => true,
                'data' => $location
            ]);
        } catch (Exception $e) {
            error_log("Error fetching location with images: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch location details'
            ], 500);
        }
    }

    /**
     * Proxy to Nominatim search (Sri Lanka-bounded)
     */
    public function search(Request $request, Response $response): void
    {
        $q = trim((string)$request->get('q', ''));
        if ($q === '') {
            $response->json(['success' => true, 'data' => []]);
            return;
        }

        $params = http_build_query([
            'format' => 'json',
            'q' => $q,
            'countrycodes' => 'lk',
            'limit' => 8,
            'addressdetails' => 1,
            'bounded' => 1,
            'viewbox' => '79.652,9.835,81.881,5.916',
        ]);

        $url = "https://nominatim.openstreetmap.org/search?{$params}";

        $ctx = stream_context_create([
            'http' => [
                'header' => "User-Agent: SkyCamp/1.0 (skycamp@example.com)\r\nAccept: application/json\r\n",
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) {
            $response->json(['success' => true, 'data' => []]);
            return;
        }

        header('Content-Type: application/json');
        echo $res;
        exit;
    }

    /**
     * Proxy to Nominatim reverse geocoding
     */
    public function reverse(Request $request, Response $response): void
    {
        $lat = (string)$request->get('lat', '');
        $lon = (string)$request->get('lon', '');
        if ($lat === '' || $lon === '') {
            $response->json(['success' => false, 'message' => 'lat/lon required'], 400);
            return;
        }

        $params = http_build_query([
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => 14,
            'addressdetails' => 1,
        ]);

        $url = "https://nominatim.openstreetmap.org/reverse?{$params}";

        $ctx = stream_context_create([
            'http' => [
                'header' => "User-Agent: SkyCamp/1.0 (skycamp@example.com)\r\nAccept: application/json\r\n",
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) {
            $response->json(['success' => true, 'data' => null]);
            return;
        }

        header('Content-Type: application/json');
        echo $res;
        exit;
    }

    /**
     * Get camping destinations with images filtered by district
     */
    public function getCampingDestinationsWithImagesByDistrict(Request $request, Response $response): void
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

            $destinations = $this->locationRepository->getCampingDestinationsWithImagesByDistrict($district);

            // Transform image paths to full URLs
            $destinations = array_map(function ($destination) {
                if ($destination['image_path']) {
                    $destination['image_url'] = 'http://localhost/skycamp' . $destination['image_path'];
                } else {
                    $destination['image_url'] = null;
                }
                return $destination;
            }, $destinations);

            $response->json([
                'success' => true,
                'data' => $destinations
            ]);
        } catch (Exception $e) {
            error_log("Error fetching camping destinations by district: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch camping destinations'
            ], 500);
        }
    }

    /**
     * Get stargazing spots with images filtered by district
     */
    public function getStargazingSpotsWithImagesByDistrict(Request $request, Response $response): void
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

            $spots = $this->locationRepository->getStargazingSpotsWithImagesByDistrict($district);

            // Transform image paths to full URLs
            $spots = array_map(function ($spot) {
                if ($spot['image_path']) {
                    $spot['image_url'] = 'http://localhost/skycamp' . $spot['image_path'];
                } else {
                    $spot['image_url'] = null;
                }
                return $spot;
            }, $spots);

            $response->json([
                'success' => true,
                'data' => $spots
            ]);
        } catch (Exception $e) {
            error_log("Error fetching stargazing spots by district: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch stargazing spots'
            ], 500);
        }
    }

    /**
     * Get all districts
     */
    public function getAllDistricts(Request $request, Response $response): void
    {
        try {
            $districts = $this->locationRepository->getAllDistricts();
            $response->json([
                'success' => true,
                'data' => $districts
            ]);
        } catch (Exception $e) {
            error_log("Error fetching districts: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch districts'
            ], 500);
        }
    }
}
