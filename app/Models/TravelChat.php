<?php

/**
 * Travel Chat Model
 * Represents a chat room for a travel plan
 */

class TravelChat
{
    public int $chatId;
    public int $planId;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        $this->chatId = (int) ($data['chat_id'] ?? 0);
        $this->planId = (int) ($data['plan_id'] ?? 0);
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'chat_id' => $this->chatId,
            'plan_id' => $this->planId,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Validate chat data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->planId)) {
            $errors['plan_id'] = 'Plan ID is required';
        }

        return $errors;
    }
}
