<?php
/**
 * Abstract User Class
 * Demonstrates Abstraction and Encapsulation OOP principles
 */
abstract class User {
    // Encapsulation: Private properties
    protected $id;
    protected $firstName;
    protected $lastName;
    protected $email;
    protected $phone;
    protected $dateOfBirth;
    protected $gender;
    protected $address;
    protected $profilePicture;
    protected $nicFront;
    protected $nicBack;
    protected $password;
    protected $userRole;
    protected $createdAt;
    protected $updatedAt;
    protected $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Encapsulation: Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    
    public function getFirstName() { return $this->firstName; }
    public function setFirstName($firstName) { $this->firstName = $firstName; }
    
    public function getLastName() { return $this->lastName; }
    public function setLastName($lastName) { $this->lastName = $lastName; }
    
    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }
    
    public function getPhone() { return $this->phone; }
    public function setPhone($phone) { $this->phone = $phone; }
    
    public function getDateOfBirth() { return $this->dateOfBirth; }
    public function setDateOfBirth($dateOfBirth) { $this->dateOfBirth = $dateOfBirth; }
    
    public function getGender() { return $this->gender; }
    public function setGender($gender) { $this->gender = $gender; }
    
    public function getAddress() { return $this->address; }
    public function setAddress($address) { $this->address = $address; }
    
    public function getProfilePicture() { return $this->profilePicture; }
    public function setProfilePicture($profilePicture) { $this->profilePicture = $profilePicture; }
    
    public function getNicFront() { return $this->nicFront; }
    public function setNicFront($nicFront) { $this->nicFront = $nicFront; }
    
    public function getNicBack() { return $this->nicBack; }
    public function setNicBack($nicBack) { $this->nicBack = $nicBack; }
    
    public function setPassword($password) { $this->password = password_hash($password, PASSWORD_DEFAULT); }
    
    public function getUserRole() { return $this->userRole; }
    public function setUserRole($userRole) { $this->userRole = $userRole; }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Abstract methods (Abstraction principle)
     * Must be implemented by child classes
     */
    abstract public function register($data);
    abstract public function getSpecificData();
    abstract public function getDashboardUrl();
}
?>
