<?php
class Renter
{
    private $conn;
    private $table_name = "renters";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get renter by user ID
     * 
     * @param string $user_id User ID
     * @return array|false Renter data on success, false on failure
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
     * Create new renter
     * 
     * @param array $data Renter data
     * @return array Result with success status and data
     */
    public function create($data)
    {
        try {
            $renter_id = $this->generateUUID();

            $query = "INSERT INTO " . $this->table_name . " 
                     (renter_id, user_id, first_name, last_name, dob, phone_number, home_address, 
                      gender, nic_number, camping_destinations, stargazing_spots, district, 
                      verification_status, latitude, longitude) 
                     VALUES 
                     (:renter_id, :user_id, :first_name, :last_name, :dob, :phone_number, :home_address, 
                      :gender, :nic_number, :camping_destinations, :stargazing_spots, :district, 
                      'No', :latitude, :longitude)";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $stmt->bindParam(':renter_id', $renter_id);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':dob', $data['dob']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':home_address', $data['home_address']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':nic_number', $data['nic_number']);
            $stmt->bindParam(':camping_destinations', $data['camping_destinations']);
            $stmt->bindParam(':stargazing_spots', $data['stargazing_spots']);
            $stmt->bindParam(':district', $data['district']);
            $stmt->bindParam(':latitude', $data['latitude']);
            $stmt->bindParam(':longitude', $data['longitude']);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'renter_id' => $renter_id,
                    'message' => 'Renter created successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create renter'
            ];
        } catch (Exception $e) {
            error_log("Renter creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }

    /**
     * Check if phone number exists
     * 
     * @param string $phone_number Phone number to check
     * @return bool True if exists, false otherwise
     */
    public function phoneExists($phone_number)
    {
        $query = "SELECT renter_id FROM " . $this->table_name . " WHERE phone_number = :phone_number LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Check if NIC number exists
     * 
     * @param string $nic_number NIC number to check
     * @return bool True if exists, false otherwise
     */
    public function nicExists($nic_number)
    {
        $query = "SELECT renter_id FROM " . $this->table_name . " WHERE nic_number = :nic_number LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $nic_upper = strtoupper($nic_number);
        $stmt->bindParam(':nic_number', $nic_upper);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Update renter profile
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
                'camping_destinations',
                'stargazing_spots',
                'district',
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
            error_log("Renter update profile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate UUID for renter
     * 
     * @return string Generated UUID
     */
    private function generateUUID()
    {
        // Simple UUID v4 generation
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
