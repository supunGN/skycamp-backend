<?php

/**
 * Reminder Repository
 * Lightweight helper to track timed reminders such as
 * Travel Buddy activation pending verification windows.
 */

class ReminderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a reminder row
     */
    public function create(int $userId, string $reason, string $severity = 'Info'): int
    {
        $sql = "INSERT INTO reminders (user_id, reason, severity, created_at) VALUES (:user_id, :reason, :severity, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'reason' => $reason,
            'severity' => $severity
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get latest reminder for a user by reason
     */
    public function getLatestByUserAndReason(int $userId, string $reason): ?array
    {
        $sql = "SELECT * FROM reminders WHERE user_id = :user_id AND reason = :reason ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'reason' => $reason
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}



