<?php

/**
 * Travel Message Repository
 * Handles database operations for travel messages
 */

class TravelMessageRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new travel message
     */
    public function create(array $data): TravelMessage
    {
        $sql = "INSERT INTO travel_messages (chat_id, sender_id, message) 
                VALUES (:chat_id, :sender_id, :message)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'chat_id' => $data['chat_id'],
            'sender_id' => $data['sender_id'],
            'message' => $data['message']
        ]);

        $messageId = $this->pdo->lastInsertId();
        return $this->findById($messageId);
    }

    /**
     * Find message by ID
     */
    public function findById(int $messageId): ?TravelMessage
    {
        $sql = "SELECT tm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_messages tm 
                JOIN customers c ON tm.sender_id = c.customer_id 
                WHERE tm.message_id = :message_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['message_id' => $messageId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new TravelMessage($data) : null;
    }

    /**
     * Find messages by chat ID with pagination
     */
    public function findByChatId(int $chatId, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT tm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_messages tm 
                JOIN customers c ON tm.sender_id = c.customer_id 
                WHERE tm.chat_id = :chat_id
                ORDER BY tm.sent_at ASC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':chat_id', $chatId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = new TravelMessage($row);
            $messages[] = [
                'message' => $message->toArray(),
                'sender' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        return $messages;
    }

    /**
     * Get recent messages for a chat (last 20 messages)
     */
    public function getRecentMessages(int $chatId, int $limit = 20): array
    {
        $sql = "SELECT tm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_messages tm 
                JOIN customers c ON tm.sender_id = c.customer_id 
                WHERE tm.chat_id = :chat_id
                ORDER BY tm.sent_at DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':chat_id', $chatId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = new TravelMessage($row);
            $messages[] = [
                'message' => $message->toArray(),
                'sender' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        // Reverse to get chronological order
        return array_reverse($messages);
    }

    /**
     * Get messages after a specific timestamp
     */
    public function getMessagesAfter(int $chatId, string $afterTimestamp): array
    {
        $sql = "SELECT tm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_messages tm 
                JOIN customers c ON tm.sender_id = c.customer_id 
                WHERE tm.chat_id = :chat_id AND tm.sent_at > :after_timestamp
                ORDER BY tm.sent_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'chat_id' => $chatId,
            'after_timestamp' => $afterTimestamp
        ]);
        
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = new TravelMessage($row);
            $messages[] = [
                'message' => $message->toArray(),
                'sender' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        return $messages;
    }

    /**
     * Count total messages in a chat
     */
    public function countByChatId(int $chatId): int
    {
        $sql = "SELECT COUNT(*) FROM travel_messages WHERE chat_id = :chat_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['chat_id' => $chatId]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Delete message
     */
    public function delete(int $messageId): bool
    {
        $sql = "DELETE FROM travel_messages WHERE message_id = :message_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['message_id' => $messageId]);
    }

    /**
     * Delete all messages for a chat
     */
    public function deleteByChatId(int $chatId): bool
    {
        $sql = "DELETE FROM travel_messages WHERE chat_id = :chat_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['chat_id' => $chatId]);
    }

    /**
     * Get last message in a chat
     */
    public function getLastMessage(int $chatId): ?array
    {
        $sql = "SELECT tm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_messages tm 
                JOIN customers c ON tm.sender_id = c.customer_id 
                WHERE tm.chat_id = :chat_id
                ORDER BY tm.sent_at DESC
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['chat_id' => $chatId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        
        $message = new TravelMessage($row);
        return [
            'message' => $message->toArray(),
            'sender' => [
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'profile_picture' => $row['profile_picture']
            ]
        ];
    }

    /**
     * Search messages in a chat
     */
    public function searchMessages(int $chatId, string $searchTerm, int $limit = 20): array
    {
        $sql = "SELECT tm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_messages tm 
                JOIN customers c ON tm.sender_id = c.customer_id 
                WHERE tm.chat_id = :chat_id AND tm.message LIKE :search_term
                ORDER BY tm.sent_at DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':chat_id', $chatId, PDO::PARAM_INT);
        $stmt->bindValue(':search_term', '%' . $searchTerm . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $message = new TravelMessage($row);
            $messages[] = [
                'message' => $message->toArray(),
                'sender' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        return $messages;
    }
}
