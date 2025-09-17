<?php

/**
 * Travel Message Model
 * Represents a message in a travel plan chat
 */

class TravelMessage
{
    public int $messageId;
    public int $chatId;
    public int $senderId;
    public string $message;
    public string $sentAt;

    public function __construct(array $data = [])
    {
        $this->messageId = (int) ($data['message_id'] ?? 0);
        $this->chatId = (int) ($data['chat_id'] ?? 0);
        $this->senderId = (int) ($data['sender_id'] ?? 0);
        $this->message = $data['message'] ?? '';
        $this->sentAt = $data['sent_at'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'chat_id' => $this->chatId,
            'sender_id' => $this->senderId,
            'message' => $this->message,
            'sent_at' => $this->sentAt
        ];
    }

    /**
     * Validate message data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->chatId)) {
            $errors['chat_id'] = 'Chat ID is required';
        }

        if (empty($this->senderId)) {
            $errors['sender_id'] = 'Sender ID is required';
        }

        if (empty($this->message)) {
            $errors['message'] = 'Message is required';
        } elseif (strlen($this->message) > 1000) {
            $errors['message'] = 'Message is too long (max 1000 characters)';
        }

        return $errors;
    }

    /**
     * Check if message is recent (within last 5 minutes)
     */
    public function isRecent(): bool
    {
        $messageTime = new DateTime($this->sentAt);
        $fiveMinutesAgo = new DateTime('-5 minutes');
        return $messageTime > $fiveMinutesAgo;
    }
}
