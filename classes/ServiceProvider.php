<?php
require_once 'User.php';

/**
 * Service Provider Class
 * Demonstrates Inheritance and Polymorphism
 */
class ServiceProvider extends User
{
    private $providerType; // 'Equipment Renter' or 'Local Guide'
    private $campingLocations = [];
    private $stargazingLocations = [];
    private $availableDistricts = [];

    public function __construct()
    {
        parent::__construct();
        $this->userRole = 'service_provider';
    }

    // Getters & Setters
    public function getProviderType()
    {
        return $this->providerType;
    }
    public function setProviderType($providerType)
    {
        $this->providerType = $providerType;
    }

    public function getCampingLocations()
    {
        return $this->campingLocations;
    }
    public function setCampingLocations($campingLocations)
    {
        $this->campingLocations = $campingLocations;
    }

    public function getStargazingLocations()
    {
        return $this->stargazingLocations;
    }
    public function setStargazingLocations($stargazingLocations)
    {
        $this->stargazingLocations = $stargazingLocations;
    }

    public function getAvailableDistricts()
    {
        return $this->availableDistricts;
    }
    public function setAvailableDistricts($availableDistricts)
    {
        $this->availableDistricts = $availableDistricts;
    }

    /**
     * Register a new service provider
     */
    public function register($data)
    {
        try {
            $this->db->beginTransaction();

            // Set base user properties
            $this->setFirstName($data['firstName']);
            $this->setLastName($data['lastName']);
            $this->setEmail($data['email']);
            $this->setPhone($data['phone']);
            $this->setDateOfBirth($data['dateOfBirth']);
            $this->setGender($data['gender']);
            $this->setAddress($data['address']);
            $this->setPassword($data['password']);

            // Set service provider fields
            $this->setProviderType($data['provider_type']);
            $this->setCampingLocations(is_array($data['camping_locations']) ? $data['camping_locations'] : json_decode($data['camping_locations'], true));
            $this->setStargazingLocations(is_array($data['stargazing_locations']) ? $data['stargazing_locations'] : json_decode($data['stargazing_locations'], true));
            $this->setAvailableDistricts(is_array($data['available_districts']) ? $data['available_districts'] : json_decode($data['available_districts'], true));

            // Handle optional files
            if (isset($data['profilePicture'])) $this->setProfilePicture($data['profilePicture']);
            if (isset($data['nicFront'])) $this->setNicFront($data['nicFront']);
            if (isset($data['nicBack'])) $this->setNicBack($data['nicBack']);

            // Insert into users
            $userStmt = $this->db->prepare("
                INSERT INTO users 
                (first_name, last_name, email, phone, date_of_birth, gender, address, 
                 profile_picture, nic_front, nic_back, password, user_role, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $userStmt->execute([
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->phone,
                $this->dateOfBirth,
                $this->gender,
                $this->address,
                $this->profilePicture,
                $this->nicFront,
                $this->nicBack,
                $this->password,
                $this->userRole
            ]);

            $userId = $this->db->lastInsertId();
            $this->setId($userId);

            // Insert into service_providers
            $providerStmt = $this->db->prepare("
                INSERT INTO service_providers 
                (user_id, provider_type, camping_locations, stargazing_locations, available_districts, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $providerStmt->execute([
                $userId,
                $this->providerType,
                json_encode($this->campingLocations),
                json_encode($this->stargazingLocations),
                json_encode($this->availableDistricts)
            ]);

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Service Provider registration failed: " . $e->getMessage());
        }
    }

    public function getSpecificData()
    {
        $stmt = $this->db->prepare("
            SELECT sp.*, u.* 
            FROM service_providers sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDashboardUrl()
    {
        // Decide dashboard based on provider type
        if (strtolower($this->providerType) === 'equipment renter') {
            return '/dashboard/renter/overview';
        }
        if (strtolower($this->providerType) === 'local guide') {
            return '/dashboard/guide/overview';
        }
        return '/dashboard';
    }

    public function login($email, $password)
    {
        $stmt = $this->db->prepare("
            SELECT u.*, sp.provider_type, sp.camping_locations, sp.stargazing_locations, sp.available_districts 
            FROM users u 
            JOIN service_providers sp ON u.id = sp.user_id 
            WHERE u.email = ? AND u.user_role = 'service_provider'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['provider_type'] = $user['provider_type'];

            unset($user['password']); // remove sensitive data

            return [
                'success' => true,
                'user' => $user,
                'redirect_url' => (strtolower($user['provider_type']) === 'equipment renter')
                    ? '/dashboard/renter/overview'
                    : '/dashboard/guide/overview'
            ];
        }
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public static function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.user_role, u.profile_picture,
               sp.provider_type, sp.camping_locations, sp.stargazing_locations, sp.available_districts
        FROM users u
        LEFT JOIN service_providers sp ON u.id = sp.user_id
        WHERE u.id = ?
    ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && empty($user['profile_picture'])) {
            unset($user['profile_picture']);
        }
        return $user;
    }


    public static function getByEmail($email)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.user_role, u.profile_picture,
               sp.provider_type, sp.camping_locations, sp.stargazing_locations, sp.available_districts
        FROM users u
        LEFT JOIN service_providers sp ON u.id = sp.user_id
        WHERE u.email = ? AND u.user_role = 'service_provider'
    ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && empty($user['profile_picture'])) {
            unset($user['profile_picture']);
        }
        return $user;
    }
}
