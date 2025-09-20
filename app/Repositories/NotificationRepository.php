<?php

namespace App\Repositories;

use App\Models\Notification;
use PDO;

class NotificationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new notification
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO notifications (user_id, type, message, is_read) VALUES (:user_id, :type, :message, :is_read)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'message' => $data['message'],
            'is_read' => $data['is_read'] ?? false,
        ]);
    }

    /**
     * Get notifications for a user
     */
    public function getByUserId(int $userId, int $limit = 50): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Notification($row), $results);
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :notification_id AND user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Delete old notifications (older than 30 days)
     */
    public function deleteOldNotifications(): int
    {
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
