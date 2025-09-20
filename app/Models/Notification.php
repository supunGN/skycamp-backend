<?php

namespace App\Models;

class Notification
{
    public int $notificationId;
    public int $userId;
    public string $type;
    public string $message;
    public bool $isRead;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->notificationId = (int)($data['notification_id'] ?? 0);
            $this->userId = (int)($data['user_id'] ?? 0);
            $this->type = $data['type'] ?? '';
            $this->message = $data['message'] ?? '';
            $this->isRead = (bool)($data['is_read'] ?? false);
            $this->createdAt = $data['created_at'] ?? '';
        }
    }

    public function toArray(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
            'type' => $this->type,
            'message' => $this->message,
            'is_read' => $this->isRead,
            'created_at' => $this->createdAt,
        ];
    }
}
