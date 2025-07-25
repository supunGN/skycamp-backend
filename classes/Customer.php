<?php
require_once 'User.php';

/**
 * Customer Class
 * Demonstrates Inheritance and Polymorphism
 */
class Customer extends User
{
    // Customer-specific properties
    private $travelBuddyOption;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->userRole = 'customer';
    }

    // Customer-specific getters and setters
    public function getTravelBuddyOption()
    {
        return $this->travelBuddyOption;
    }
    public function setTravelBuddyOption($travelBuddyOption)
    {
        $this->travelBuddyOption = $travelBuddyOption;
    }

    /**
     * Polymorphism: Customer-specific registration implementation
     */
    public function register($data)
    {
        try {
            $this->db->beginTransaction();

            // Set user properties
            $this->setFirstName($data['firstName']);
            $this->setLastName($data['lastName']);
            $this->setEmail($data['email']);
            $this->setPhone($data['phone']);
            $this->setDateOfBirth($data['dateOfBirth']);
            $this->setGender($data['gender']);
            $this->setAddress($data['address']);
            $this->setPassword($data['password']);
            $this->setTravelBuddyOption($data['travelBuddyOption']);

            // Handle file uploads if provided
            if (isset($data['profilePicture'])) {
                $this->setProfilePicture($data['profilePicture']);
            }
            if (isset($data['nicFront'])) {
                $this->setNicFront($data['nicFront']);
            }
            if (isset($data['nicBack'])) {
                $this->setNicBack($data['nicBack']);
            }

            // Insert into users table
            $userStmt = $this->db->prepare("
                INSERT INTO users (first_name, last_name, email, phone, date_of_birth, 
                                 gender, address, profile_picture, nic_front, nic_back, 
                                 password, user_role, created_at, updated_at) 
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

            // Insert into customers table
            $customerStmt = $this->db->prepare("
                INSERT INTO customers (user_id, travel_buddy_option, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW())
            ");

            $customerStmt->execute([$userId, $this->travelBuddyOption]);

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Customer registration failed: " . $e->getMessage());
        }
    }

    /**
     * Get customer-specific data
     */
    public function getSpecificData()
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.* FROM customers c 
            JOIN users u ON c.user_id = u.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch();
    }

    /**
     * Get dashboard URL for customer
     */
    public function getDashboardUrl()
    {
        return '/'; // Customer goes to home page
    }

    /**
     * Login customer
     */
    public function login($email, $password)
    {
        $stmt = $this->db->prepare("
            SELECT u.*, c.travel_buddy_option 
            FROM users u 
            JOIN customers c ON u.id = c.user_id 
            WHERE u.email = ? AND u.user_role = 'customer'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

            // Remove password before returning
            unset($user['password']);

            return [
                'success' => true,
                'user' => $user,
                'redirect_url' => $this->getDashboardUrl()
            ];
        }

        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    /**
     * Fetch minimal user data by ID
     */
    public static function getById($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, first_name, last_name, email, user_role, profile_picture, gender, phone, date_of_birth FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && empty($user['profile_picture'])) {
            unset($user['profile_picture']);
        }
        return $user;
    }

    /**
     * Fetch minimal user data by email
     */
    public static function getByEmail($email)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, first_name, last_name, email, user_role, profile_picture FROM users WHERE email = ? AND user_role = 'customer'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && empty($user['profile_picture'])) {
            unset($user['profile_picture']);
        }
        return $user;
    }
}
