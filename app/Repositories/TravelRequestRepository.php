<?php

/**
 * Travel Request Repository
 * Handles database operations for travel requests
 */

class TravelRequestRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new travel request
     */
    public function create(array $data): TravelRequest
    {
        $sql = "INSERT INTO travel_requests (plan_id, requester_id, status) 
                VALUES (:plan_id, :requester_id, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'plan_id' => $data['plan_id'],
            'requester_id' => $data['requester_id'],
            'status' => $data['status']
        ]);

        $requestId = $this->pdo->lastInsertId();
        return $this->findById($requestId);
    }

    /**
     * Find travel request by ID
     */
    public function findById(int $requestId): ?TravelRequest
    {
        $sql = "SELECT tr.*, c.first_name, c.last_name, c.profile_picture,
                       tp.destination, tp.travel_date
                FROM travel_requests tr 
                JOIN customers c ON tr.requester_id = c.customer_id 
                JOIN travel_plans tp ON tr.plan_id = tp.plan_id
                WHERE tr.request_id = :request_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['request_id' => $requestId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new TravelRequest($data) : null;
    }

    /**
     * Find request by plan and requester
     */
    public function findByPlanAndRequester(int $planId, int $requesterId): ?TravelRequest
    {
        $sql = "SELECT * FROM travel_requests 
                WHERE plan_id = :plan_id AND requester_id = :requester_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'plan_id' => $planId,
            'requester_id' => $requesterId
        ]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new TravelRequest($data) : null;
    }

    /**
     * Find all requests for a travel plan
     */
    public function findByPlanId(int $planId, string $status = null): array
    {
        $sql = "SELECT tr.*, c.first_name, c.last_name, c.profile_picture, c.phone_number
                FROM travel_requests tr 
                JOIN customers c ON tr.requester_id = c.customer_id 
                WHERE tr.plan_id = :plan_id";
        
        $params = ['plan_id' => $planId];
        
        if ($status) {
            $sql .= " AND tr.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY tr.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $requests = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $request = new TravelRequest($row);
            $requests[] = [
                'request' => $request->toArray(),
                'requester' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture'],
                    'phone_number' => $row['phone_number']
                ]
            ];
        }
        
        return $requests;
    }

    /**
     * Find all requests by a customer
     */
    public function findByRequesterId(int $requesterId): array
    {
        $sql = "SELECT tr.*, c.first_name, c.last_name, c.profile_picture,
                       tp.destination, tp.travel_date, tp.companions_needed, tp.companions_joined
                FROM travel_requests tr 
                JOIN customers c ON tr.requester_id = c.customer_id 
                JOIN travel_plans tp ON tr.plan_id = tp.plan_id
                WHERE tr.requester_id = :requester_id
                ORDER BY tr.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['requester_id' => $requesterId]);
        
        $requests = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $request = new TravelRequest($row);
            $requests[] = [
                'request' => $request->toArray(),
                'plan' => [
                    'destination' => $row['destination'],
                    'travel_date' => $row['travel_date'],
                    'companions_needed' => (int) $row['companions_needed'],
                    'companions_joined' => (int) $row['companions_joined']
                ],
                'plan_creator' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        return $requests;
    }

    /**
     * Update request status
     */
    public function updateStatus(int $requestId, string $status): bool
    {
        $sql = "UPDATE travel_requests SET status = :status WHERE request_id = :request_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'request_id' => $requestId,
            'status' => $status
        ]);
    }

    /**
     * Accept a travel request
     */
    public function acceptRequest(int $requestId): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Update request status
            $sql = "UPDATE travel_requests SET status = 'Accepted' WHERE request_id = :request_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['request_id' => $requestId]);
            
            // Get request details
            $request = $this->findById($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Update companions joined count
            $planRepo = new TravelPlanRepository();
            $plan = $planRepo->findById($request->planId);
            if (!$plan) {
                throw new Exception('Travel plan not found');
            }
            
            $newCompanionsJoined = $plan->companionsJoined + 1;
            $planRepo->updateCompanionsJoined($request->planId, $newCompanionsJoined);
            
            // Add requester to chat
            $chatRepo = new TravelChatRepository();
            $chat = $chatRepo->findByPlanId($request->planId);
            if ($chat) {
                $chatRepo->addMember($chat->chatId, $request->requesterId);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Reject a travel request
     */
    public function rejectRequest(int $requestId): bool
    {
        $sql = "UPDATE travel_requests SET status = 'Rejected' WHERE request_id = :request_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['request_id' => $requestId]);
    }

    /**
     * Delete travel request
     */
    public function delete(int $requestId): bool
    {
        $sql = "DELETE FROM travel_requests WHERE request_id = :request_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['request_id' => $requestId]);
    }

    /**
     * Count pending requests for a plan
     */
    public function countPendingByPlanId(int $planId): int
    {
        $sql = "SELECT COUNT(*) FROM travel_requests 
                WHERE plan_id = :plan_id AND status = 'Pending'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['plan_id' => $planId]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count requests by requester
     */
    public function countByRequesterId(int $requesterId, string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM travel_requests WHERE requester_id = :requester_id";
        $params = ['requester_id' => $requesterId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get recent requests for dashboard
     */
    public function getRecentRequests(int $customerId, int $limit = 5): array
    {
        $sql = "SELECT tr.*, c.first_name, c.last_name, c.profile_picture,
                       tp.destination, tp.travel_date
                FROM travel_requests tr 
                JOIN customers c ON tr.requester_id = c.customer_id 
                JOIN travel_plans tp ON tr.plan_id = tp.plan_id
                WHERE tp.customer_id = :customer_id
                ORDER BY tr.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $requests = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $request = new TravelRequest($row);
            $requests[] = [
                'request' => $request->toArray(),
                'requester' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ],
                'plan' => [
                    'destination' => $row['destination'],
                    'travel_date' => $row['travel_date']
                ]
            ];
        }
        
        return $requests;
    }
}
