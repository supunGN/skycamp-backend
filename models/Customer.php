<?php

/**
 * Customer Model Class
 * Handles all Customer table database operations
 * Following OOP principles and MVC architecture
 */

require_once __DIR__ . '/../config/Database.php';

class Customer
{
    // Database connection
    private $conn;
    private $table_name = "customers";

    // Customer properties - Encapsulation
    private $customer_id;
    private $user_id;
    private $first_name;
    private $last_name;
    private $dob;
    private $phone_number;
    private $home_address;
    private $gender;
    private $profile_picture;
    private $nic_number;
    private $nic_image;
    private $travel_buddy_status;
    private $verification_status;
    private $latitude;
    private $longitude;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct($db = null)
    {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Setters - Following encapsulation principle with validation
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setFirstName($first_name)
    {
        $this->first_name = htmlspecialchars(strip_tags(trim($first_name)));
    }

    public function setLastName($last_name)
    {
        $this->last_name = htmlspecialchars(strip_tags(trim($last_name)));
    }

    public function setDob($dob)
    {
        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d', $dob);
        if ($date && $date->format('Y-m-d') === $dob) {
            $this->dob = $dob;
        } else {
            throw new InvalidArgumentException("Invalid date format for date of birth");
        }
    }

    public function setPhoneNumber($phone_number)
    {
        // Validate Sri Lankan phone number format
        $phone = preg_replace('/[^0-9]/', '', $phone_number);
        if (preg_match('/^0[1-9][0-9]{8}$/', $phone)) {
            $this->phone_number = $phone;
        } else {
            throw new InvalidArgumentException("Invalid Sri Lankan phone number format");
        }
    }

    public function setHomeAddress($home_address)
    {
        $this->home_address = htmlspecialchars(strip_tags(trim($home_address)));
    }

    public function setGender($gender)
    {
        $valid_genders = ['Male', 'Female', 'Other'];
        if (in_array($gender, $valid_genders)) {
            $this->gender = $gender;
        } else {
            throw new InvalidArgumentException("Invalid gender value");
        }
    }

    public function setProfilePicture($profile_picture)
    {
        $this->profile_picture = $profile_picture;
    }

    public function setNicNumber($nic_number)
    {
        // Validate Sri Lankan NIC format
        $nic = strtoupper(trim($nic_number));
        if (preg_match('/^[0-9]{9}[VX]$/', $nic)) {
            $this->nic_number = $nic;
        } else {
            throw new InvalidArgumentException("Invalid NIC number format");
        }
    }

    public function setNicImage($nic_image)
    {
        $this->nic_image = $nic_image;
    }

    public function setTravelBuddyStatus($status)
    {
        $valid_statuses = ['Active', 'Inactive'];
        if (in_array($status, $valid_statuses)) {
            $this->travel_buddy_status = $status;
        } else {
            $this->travel_buddy_status = 'Inactive'; // Default
        }
    }

    public function setVerificationStatus($status)
    {
        $valid_statuses = ['Yes', 'No'];
        if (in_array($status, $valid_statuses)) {
            $this->verification_status = $status;
        } else {
            $this->verification_status = 'No'; // Default
        }
    }

    public function setLatitude($latitude)
    {
        if (is_numeric($latitude) && $latitude >= -90 && $latitude <= 90) {
            $this->latitude = (float)$latitude;
        }
    }

    public function setLongitude($longitude)
    {
        if (is_numeric($longitude) && $longitude >= -180 && $longitude <= 180) {
            $this->longitude = (float)$longitude;
        }
    }

    /**
     * Getters - Following encapsulation principle
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getNicNumber()
    {
        return $this->nic_number;
    }

    /**
     * Create new customer in database
     * Uses prepared statements to prevent SQL injection
     * 
     * @return string|false Customer ID on success, false on failure
     */
    public function create()
    {
        // Generate UUID for customer_id
        $this->customer_id = $this->generateUUID();

        // SQL query with placeholders
        $query = "INSERT INTO " . $this->table_name . " 
                  (customer_id, user_id, first_name, last_name, dob, phone_number, 
                   home_address, gender, profile_picture, nic_number, nic_image, 
                   travel_buddy_status, verification_status, latitude, longitude, created_at) 
                  VALUES (:customer_id, :user_id, :first_name, :last_name, :dob, :phone_number, 
                          :home_address, :gender, :profile_picture, :nic_number, :nic_image, 
                          :travel_buddy_status, :verification_status, :latitude, :longitude, NOW())";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind values - Security measure
        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':dob', $this->dob);
        $stmt->bindParam(':phone_number', $this->phone_number);
        $stmt->bindParam(':home_address', $this->home_address);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':profile_picture', $this->profile_picture);
        $stmt->bindParam(':nic_number', $this->nic_number);
        $stmt->bindParam(':nic_image', $this->nic_image);
        $stmt->bindParam(':travel_buddy_status', $this->travel_buddy_status);
        $stmt->bindParam(':verification_status', $this->verification_status);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);

        // Execute query
        if ($stmt->execute()) {
            return $this->customer_id;
        }

        // Log error for debugging
        error_log("Customer creation failed: " . implode(", ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Check if NIC number already exists
     * 
     * @param string $nic_number NIC number to check
     * @return bool True if exists, false otherwise
     */
    public function nicExists($nic_number)
    {
        $query = "SELECT customer_id FROM " . $this->table_name . " WHERE nic_number = :nic_number LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $nic_upper = strtoupper($nic_number);
        $stmt->bindParam(':nic_number', $nic_upper);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get customer by user ID
     * 
     * @param string $user_id User ID
     * @return array|false Customer data on success, false on failure
     */
    public function getCustomerByUserId($user_id)
    {
        $query = "SELECT c.*, u.email, u.role, u.is_active 
                  FROM " . $this->table_name . " c 
                  JOIN users u ON c.user_id = u.user_id 
                  WHERE c.user_id = :user_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Get customer by customer ID
     * 
     * @param string $customer_id Customer ID
     * @return array|false Customer data on success, false on failure
     */
    public function getCustomerById($customer_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE customer_id = :customer_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Update customer verification status
     * 
     * @param string $customer_id Customer ID
     * @param string $status Verification status
     * @return bool Success status
     */
    public function updateVerificationStatus($customer_id, $status)
    {
        $query = "UPDATE " . $this->table_name . " SET verification_status = :status WHERE customer_id = :customer_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    /**
     * Update travel buddy status
     * 
     * @param string $customer_id Customer ID
     * @param string $status Travel buddy status
     * @return bool Success status
     */
    public function updateTravelBuddyStatus($customer_id, $status)
    {
        $query = "UPDATE " . $this->table_name . " SET travel_buddy_status = :status WHERE customer_id = :customer_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    /**
     * Generate UUID for customer_id
     * 
     * @return string UUID
     */
    private function generateUUID()
    {
        // Simple UUID v4 generation
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Get customer by user ID
     * 
     * @param string $user_id User ID
     * @return array|false Customer data on success, false on failure
     */
    public function getByUserId($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Update customer profile
     * 
     * @param string $user_id User ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function updateProfile($user_id, $data)
    {
        try {
            // Build dynamic query based on provided data
            $setParts = [];
            $values = [];

            $allowedFields = [
                'first_name',
                'last_name',
                'dob',
                'phone_number',
                'home_address',
                'gender',
                'profile_picture',
                'nic_number',
                'nic_image',
                'travel_buddy_status',
                'latitude',
                'longitude'
            ];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $setParts[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($setParts)) {
                return true; // No data to update
            }

            $values[] = $user_id; // Add user_id for WHERE clause

            $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $setParts) . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);

            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Customer update profile error: " . $e->getMessage());
            return false;
        }
    }
}
