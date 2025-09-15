<?php

/**
 * Travel Chat Repository
 * Handles database operations for travel chats
 */

class TravelChatRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new travel chat
     */
    public function create(array $data): TravelChat
    {
        $sql = "INSERT INTO travel_chats (plan_id) VALUES (:plan_id)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['plan_id' => $data['plan_id']]);

        $chatId = $this->pdo->lastInsertId();
        return $this->findById($chatId);
    }

    /**
     * Find chat by ID
     */
    public function findById(int $chatId): ?TravelChat
    {
        $sql = "SELECT * FROM travel_chats WHERE chat_id = :chat_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['chat_id' => $chatId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new TravelChat($data) : null;
    }

    /**
     * Find chat by plan ID
     */
    public function findByPlanId(int $planId): ?TravelChat
    {
        $sql = "SELECT * FROM travel_chats WHERE plan_id = :plan_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['plan_id' => $planId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new TravelChat($data) : null;
    }

    /**
     * Add member to chat
     */
    public function addMember(int $chatId, int $customerId): bool
    {
        // Check if already a member
        if ($this->isMember($chatId, $customerId)) {
            return true;
        }

        $sql = "INSERT INTO travel_chat_members (chat_id, customer_id, status) 
                VALUES (:chat_id, :customer_id, 'Active')";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'chat_id' => $chatId,
            'customer_id' => $customerId
        ]);
    }

    /**
     * Remove member from chat
     */
    public function removeMember(int $chatId, int $customerId): bool
    {
        $sql = "UPDATE travel_chat_members 
                SET status = 'Left', left_at = NOW() 
                WHERE chat_id = :chat_id AND customer_id = :customer_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'chat_id' => $chatId,
            'customer_id' => $customerId
        ]);
    }

    /**
     * Check if customer is a member of the chat
     */
    public function isMember(int $chatId, int $customerId): bool
    {
        $sql = "SELECT COUNT(*) FROM travel_chat_members 
                WHERE chat_id = :chat_id AND customer_id = :customer_id AND status = 'Active'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'chat_id' => $chatId,
            'customer_id' => $customerId
        ]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get all members of a chat
     */
    public function getMembers(int $chatId): array
    {
        $sql = "SELECT tcm.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_chat_members tcm 
                JOIN customers c ON tcm.customer_id = c.customer_id 
                WHERE tcm.chat_id = :chat_id AND tcm.status = 'Active'
                ORDER BY tcm.joined_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['chat_id' => $chatId]);
        
        $members = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $members[] = [
                'customer_id' => $row['customer_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'profile_picture' => $row['profile_picture'],
                'joined_at' => $row['joined_at']
            ];
        }
        
        return $members;
    }

    /**
     * Get member count for a chat
     */
    public function getMemberCount(int $chatId): int
    {
        $sql = "SELECT COUNT(*) FROM travel_chat_members 
                WHERE chat_id = :chat_id AND status = 'Active'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['chat_id' => $chatId]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Delete chat
     */
    public function delete(int $chatId): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Delete chat members
            $sql = "DELETE FROM travel_chat_members WHERE chat_id = :chat_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['chat_id' => $chatId]);
            
            // Delete messages
            $sql = "DELETE FROM travel_messages WHERE chat_id = :chat_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['chat_id' => $chatId]);
            
            // Delete chat
            $sql = "DELETE FROM travel_chats WHERE chat_id = :chat_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['chat_id' => $chatId]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Get chat info with member details
     */
    public function getChatInfo(int $chatId): ?array
    {
        $sql = "SELECT tc.*, tp.destination, tp.travel_date,
                       COUNT(tcm.customer_id) as member_count
                FROM travel_chats tc 
                JOIN travel_plans tp ON tc.plan_id = tp.plan_id
                LEFT JOIN travel_chat_members tcm ON tc.chat_id = tcm.chat_id AND tcm.status = 'Active'
                WHERE tc.chat_id = :chat_id
                GROUP BY tc.chat_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['chat_id' => $chatId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        
        return [
            'chat' => new TravelChat($data),
            'plan' => [
                'destination' => $data['destination'],
                'travel_date' => $data['travel_date']
            ],
            'member_count' => (int) $data['member_count']
        ];
    }
}
