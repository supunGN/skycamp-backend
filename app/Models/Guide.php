<?php

/**
 * Guide Model
 * Represents a guide user (matches existing guides table)
 */

class Guide
{
    public string $guideId;
    public string $userId;
    public string $firstName;
    public string $lastName;
    public ?string $dob;
    public string $phoneNumber;
    public ?string $homeAddress;
    public string $gender;
    public ?string $profilePicture;
    public string $nicNumber;
    public ?string $nicImage;
    public ?string $campingDestinations;
    public ?string $stargazingSpots;
    public ?string $district;
    public ?string $description;
    public ?string $specialNote;
    public ?string $currency;
    public ?string $languages;
    public ?float $pricePerDay;
    public string $verificationStatus;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->guideId = $data['guide_id'] ?? '';
        $this->userId = $data['user_id'] ?? '';
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->dob = $data['dob'] ?? null;
        $this->phoneNumber = $data['phone_number'] ?? '';
        $this->homeAddress = $data['home_address'] ?? null;
        $this->gender = $data['gender'] ?? 'Male';
        $this->profilePicture = $data['profile_picture'] ?? null;
        $this->nicNumber = $data['nic_number'] ?? '';
        $this->nicImage = $data['nic_image'] ?? null;
        $this->campingDestinations = $data['camping_destinations'] ?? null;
        $this->stargazingSpots = $data['stargazing_spots'] ?? null;
        $this->district = $data['district'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->specialNote = $data['special_note'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $this->languages = $data['languages'] ?? null;
        $this->pricePerDay = $data['price_per_day'] ? (float) $data['price_per_day'] : null;
        $this->verificationStatus = $data['verification_status'] ?? 'No';
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'guide_id' => $this->guideId,
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'dob' => $this->dob,
            'phone_number' => $this->phoneNumber,
            'home_address' => $this->homeAddress,
            'gender' => $this->gender,
            'profile_picture' => $this->profilePicture,
            'nic_number' => $this->nicNumber,
            'nic_image' => $this->nicImage,
            'camping_destinations' => $this->campingDestinations,
            'stargazing_spots' => $this->stargazingSpots,
            'district' => $this->district,
            'description' => $this->description,
            'special_note' => $this->specialNote,
            'currency' => $this->currency,
            'languages' => $this->languages,
            'price_per_day' => $this->pricePerDay,
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
     * Validate guide data
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

        if ($this->pricePerDay !== null && $this->pricePerDay < 0) {
            $errors['price_per_day'] = 'Price per day cannot be negative';
        }

        return $errors;
    }
}
