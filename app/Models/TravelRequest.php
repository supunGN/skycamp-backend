<?php

/**
 * Travel Request Model
 * Represents a request to join a travel plan
 */

class TravelRequest
{
    public int $requestId;
    public int $planId;
    public int $requesterId;
    public string $status;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->requestId = (int) ($data['request_id'] ?? 0);
        $this->planId = (int) ($data['plan_id'] ?? 0);
        $this->requesterId = (int) ($data['requester_id'] ?? 0);
        $this->status = $data['status'] ?? 'Pending';
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'plan_id' => $this->planId,
            'requester_id' => $this->requesterId,
            'status' => $this->status,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Validate travel request data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->planId)) {
            $errors['plan_id'] = 'Plan ID is required';
        }

        if (empty($this->requesterId)) {
            $errors['requester_id'] = 'Requester ID is required';
        }

        if (!in_array($this->status, ['Pending', 'Accepted', 'Rejected'])) {
            $errors['status'] = 'Invalid status';
        }

        return $errors;
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if request is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'Accepted';
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'Rejected';
    }
}
