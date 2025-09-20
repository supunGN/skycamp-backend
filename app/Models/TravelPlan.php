<?php

/**
 * Travel Plan Model
 * Represents a travel plan created by a customer
 */

class TravelPlan
{
    public int $planId;
    public int $customerId;
    public string $destination;
    public string $travelDate;
    public ?string $notes;
    public int $companionsNeeded;
    public int $companionsJoined;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->planId = (int) ($data['plan_id'] ?? 0);
        $this->customerId = (int) ($data['customer_id'] ?? 0);
        $this->destination = $data['destination'] ?? '';
        $this->travelDate = $data['travel_date'] ?? '';
        $this->notes = $data['notes'] ?? null;
        $this->companionsNeeded = (int) ($data['companions_needed'] ?? 0);
        $this->companionsJoined = (int) ($data['companions_joined'] ?? 0);
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'plan_id' => $this->planId,
            'customer_id' => $this->customerId,
            'destination' => $this->destination,
            'travel_date' => $this->travelDate,
            'notes' => $this->notes,
            'companions_needed' => $this->companionsNeeded,
            'companions_joined' => $this->companionsJoined,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Validate travel plan data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->customerId)) {
            $errors['customer_id'] = 'Customer ID is required';
        }

        if (empty($this->destination)) {
            $errors['destination'] = 'Destination is required';
        } elseif (strlen($this->destination) > 255) {
            $errors['destination'] = 'Destination is too long (max 255 characters)';
        }

        if (empty($this->travelDate)) {
            $errors['travel_date'] = 'Travel date is required';
        } elseif (!$this->isValidDate($this->travelDate)) {
            $errors['travel_date'] = 'Invalid travel date format';
        } elseif (!$this->isFutureDate($this->travelDate)) {
            $errors['travel_date'] = 'Travel date must be in the future';
        }

        if ($this->companionsNeeded < 1) {
            $errors['companions_needed'] = 'At least 1 companion is required';
        } elseif ($this->companionsNeeded > 10) {
            $errors['companions_needed'] = 'Maximum 10 companions allowed';
        }

        if ($this->notes && strlen($this->notes) > 1000) {
            $errors['notes'] = 'Notes are too long (max 1000 characters)';
        }

        return $errors;
    }

    /**
     * Check if date is valid format
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Check if date is in the future
     */
    private function isFutureDate(string $date): bool
    {
        $travelDate = new DateTime($date);
        $today = new DateTime();
        return $travelDate > $today;
    }

    /**
     * Check if plan is full
     */
    public function isFull(): bool
    {
        return $this->companionsJoined >= $this->companionsNeeded;
    }

    /**
     * Get available spots
     */
    public function getAvailableSpots(): int
    {
        return max(0, $this->companionsNeeded - $this->companionsJoined);
    }
}
