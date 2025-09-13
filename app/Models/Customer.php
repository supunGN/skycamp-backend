<?php

/**
 * Customer Model
 * Represents a customer user (matches existing customers table)
 */

class Customer
{
    public string $customerId;
    public string $userId;
    public string $firstName;
    public string $lastName;
    public ?string $dob;
    public string $phoneNumber;
    public ?string $homeAddress;
    public ?string $location;
    public ?float $latitude;
    public ?float $longitude;
    public string $gender;
    public ?string $profilePicture;
    public string $nicNumber;
    public ?string $nicImage;
    public string $travelBuddyStatus;
    public string $verificationStatus;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->customerId = $data['customer_id'] ?? '';
        $this->userId = $data['user_id'] ?? '';
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->dob = $data['dob'] ?? null;
        $this->phoneNumber = $data['phone_number'] ?? '';
        $this->homeAddress = $data['home_address'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->latitude = !empty($data['latitude']) ? (float) $data['latitude'] : null;
        $this->longitude = !empty($data['longitude']) ? (float) $data['longitude'] : null;
        $this->gender = $data['gender'] ?? 'Male';
        $this->profilePicture = $data['profile_picture'] ?? null;
        $this->nicNumber = $data['nic_number'] ?? '';
        $this->nicImage = $data['nic_image'] ?? null;
        $this->travelBuddyStatus = $data['travel_buddy_status'] ?? 'Inactive';
        $this->verificationStatus = $data['verification_status'] ?? 'No';
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'dob' => $this->dob,
            'phone_number' => $this->phoneNumber,
            'home_address' => $this->homeAddress,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'gender' => $this->gender,
            'profile_picture' => $this->profilePicture,
            'nic_number' => $this->nicNumber,
            'nic_image' => $this->nicImage,
            'travel_buddy_status' => $this->travelBuddyStatus,
            'verification_status' => $this->verificationStatus,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Validate customer data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->userId)) {
            $errors['user_id'] = 'User ID is required';
        }

        if (empty($this->firstName)) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($this->lastName)) {
            $errors['last_name'] = 'Last name is required';
        }

        if (empty($this->phoneNumber)) {
            $errors['phone_number'] = 'Phone number is required';
        } elseif (!preg_match('/^0[1-9][0-9]{8}$/', $this->phoneNumber)) {
            $errors['phone_number'] = 'Invalid phone number format';
        }

        if (empty($this->nicNumber)) {
            $errors['nic_number'] = 'NIC number is required';
        } elseif (!preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $this->nicNumber)) {
            $errors['nic_number'] = 'Invalid NIC number format';
        }

        if (!in_array($this->gender, ['Male', 'Female', 'Other'])) {
            $errors['gender'] = 'Invalid gender';
        }

        return $errors;
    }
}
