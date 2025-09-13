<?php

class LocationRepository
{
    private $db;

    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Get all camping destinations
     */
    public function getCampingDestinations()
    {
        $stmt = $this->db->prepare("
            SELECT location_id, name, district, description, latitude, longitude 
            FROM locations 
            WHERE type = 'Camping' 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all stargazing spots
     */
    public function getStargazingSpots()
    {
        $stmt = $this->db->prepare("
            SELECT location_id, name, district, description, latitude, longitude 
            FROM locations 
            WHERE type = 'Stargazing' 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all locations (both camping and stargazing)
     */
    public function getAllLocations()
    {
        $stmt = $this->db->prepare("
            SELECT location_id, name, type, district, description, latitude, longitude 
            FROM locations 
            ORDER BY type ASC, name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get locations by type
     */
    public function getLocationsByType($type)
    {
        $stmt = $this->db->prepare("
            SELECT location_id, name, district, description, latitude, longitude 
            FROM locations 
            WHERE type = ? 
            ORDER BY name ASC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get location by ID
     */
    public function getLocationById($locationId)
    {
        $stmt = $this->db->prepare("
            SELECT location_id, name, type, district, description, latitude, longitude 
            FROM locations 
            WHERE location_id = ?
        ");
        $stmt->execute([$locationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get camping destinations with their first image
     */
    public function getCampingDestinationsWithImages()
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.location_id, 
                l.name, 
                l.district, 
                l.description, 
                l.latitude, 
                l.longitude,
                li.image_path
            FROM locations l
            LEFT JOIN (
                SELECT location_id, MIN(image_id) as first_image_id
                FROM location_images 
                GROUP BY location_id
            ) first_img ON l.location_id = first_img.location_id
            LEFT JOIN location_images li ON first_img.first_image_id = li.image_id
            WHERE l.type = 'Camping' 
            ORDER BY l.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get stargazing spots with their first image
     */
    public function getStargazingSpotsWithImages()
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.location_id, 
                l.name, 
                l.district, 
                l.description, 
                l.latitude, 
                l.longitude,
                li.image_path
            FROM locations l
            LEFT JOIN (
                SELECT location_id, MIN(image_id) as first_image_id
                FROM location_images 
                GROUP BY location_id
            ) first_img ON l.location_id = first_img.location_id
            LEFT JOIN location_images li ON first_img.first_image_id = li.image_id
            WHERE l.type = 'Stargazing' 
            ORDER BY l.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top 3 camping destinations with their first image for home page
     */
    public function getTopCampingDestinationsWithImages($limit = 3)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.location_id, 
                l.name, 
                l.district, 
                l.description, 
                l.latitude, 
                l.longitude,
                li.image_path
            FROM locations l
            LEFT JOIN (
                SELECT location_id, MIN(image_id) as first_image_id
                FROM location_images 
                GROUP BY location_id
            ) first_img ON l.location_id = first_img.location_id
            LEFT JOIN location_images li ON first_img.first_image_id = li.image_id
            WHERE l.type = 'Camping' 
            ORDER BY l.name ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get individual location with all details and images
     */
    public function getLocationWithImages($locationId)
    {
        // Get location details
        $stmt = $this->db->prepare("
            SELECT 
                location_id, 
                name, 
                type, 
                district, 
                description, 
                climate, 
                wildlife, 
                water_resources, 
                safety_tips, 
                important_details, 
                latitude, 
                longitude
            FROM locations 
            WHERE location_id = ?
        ");
        $stmt->execute([$locationId]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            return null;
        }

        // Get all images for this location
        $stmt = $this->db->prepare("
            SELECT image_id, image_path 
            FROM location_images 
            WHERE location_id = ? 
            ORDER BY image_id ASC
        ");
        $stmt->execute([$locationId]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add images to location data
        $location['images'] = $images;

        return $location;
    }

    /**
     * Get top 3 stargazing spots with their first image for home page
     */
    public function getTopStargazingSpotsWithImages($limit = 3)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.location_id, 
                l.name, 
                l.district, 
                l.description, 
                l.latitude, 
                l.longitude,
                li.image_path
            FROM locations l
            LEFT JOIN (
                SELECT location_id, MIN(image_id) as first_image_id
                FROM location_images 
                GROUP BY location_id
            ) first_img ON l.location_id = first_img.location_id
            LEFT JOIN location_images li ON first_img.first_image_id = li.image_id
            WHERE l.type = 'Stargazing' 
            ORDER BY l.name ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get camping destinations with images filtered by district
     */
    public function getCampingDestinationsWithImagesByDistrict($district)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.location_id, 
                l.name, 
                l.district, 
                l.description, 
                l.latitude, 
                l.longitude,
                li.image_path
            FROM locations l
            LEFT JOIN (
                SELECT location_id, MIN(image_id) as first_image_id
                FROM location_images 
                GROUP BY location_id
            ) first_img ON l.location_id = first_img.location_id
            LEFT JOIN location_images li ON first_img.first_image_id = li.image_id
            WHERE l.type = 'Camping' AND l.district = ?
            ORDER BY l.name ASC
        ");
        $stmt->execute([$district]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get stargazing spots with images filtered by district
     */
    public function getStargazingSpotsWithImagesByDistrict($district)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.location_id, 
                l.name, 
                l.district, 
                l.description, 
                l.latitude, 
                l.longitude,
                li.image_path
            FROM locations l
            LEFT JOIN (
                SELECT location_id, MIN(image_id) as first_image_id
                FROM location_images 
                GROUP BY location_id
            ) first_img ON l.location_id = first_img.location_id
            LEFT JOIN location_images li ON first_img.first_image_id = li.image_id
            WHERE l.type = 'Stargazing' AND l.district = ?
            ORDER BY l.name ASC
        ");
        $stmt->execute([$district]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all unique districts from locations table
     */
    public function getAllDistricts()
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT district 
            FROM locations 
            ORDER BY district ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
