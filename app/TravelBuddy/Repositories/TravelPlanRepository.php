<?php

/**
 * Travel Plan Repository
 * Handles database operations for travel plans
 */

class TravelPlanRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a new travel plan
     */
    public function create(array $data): TravelPlan
    {
        $sql = "INSERT INTO travel_plans (customer_id, destination, travel_date, notes, companions_needed, companions_joined) 
                VALUES (:customer_id, :destination, :travel_date, :notes, :companions_needed, :companions_joined)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'customer_id' => $data['customer_id'],
            'destination' => $data['destination'],
            'travel_date' => $data['travel_date'],
            'notes' => $data['notes'],
            'companions_needed' => $data['companions_needed'],
            'companions_joined' => $data['companions_joined']
        ]);

        $planId = $this->pdo->lastInsertId();
        return $this->findById($planId);
    }

    /**
     * Find travel plan by ID
     */
    public function findById(int $planId): ?TravelPlan
    {
        $sql = "SELECT tp.*, c.first_name, c.last_name, c.profile_picture 
                FROM travel_plans tp 
                JOIN customers c ON tp.customer_id = c.customer_id 
                WHERE tp.plan_id = :plan_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['plan_id' => $planId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new TravelPlan($data) : null;
    }

    /**
     * Find all travel plans with pagination and filters
     */
    public function findAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT tp.*, c.first_name, c.last_name, c.profile_picture,
                       COUNT(tr.request_id) as request_count
                FROM travel_plans tp 
                JOIN customers c ON tp.customer_id = c.customer_id 
                LEFT JOIN travel_requests tr ON tp.plan_id = tr.plan_id AND tr.status = 'Pending'
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['destination'])) {
            $sql .= " AND tp.destination LIKE :destination";
            $params['destination'] = '%' . $filters['destination'] . '%';
        }
        
        if (!empty($filters['travel_date'])) {
            $sql .= " AND tp.travel_date = :travel_date";
            $params['travel_date'] = $filters['travel_date'];
        }
        
        if (!empty($filters['future_only'])) {
            $sql .= " AND tp.travel_date > CURDATE()";
        }
        
        $sql .= " GROUP BY tp.plan_id 
                  ORDER BY tp.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        
        $plans = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $plan = new TravelPlan($row);
            $plans[] = [
                'plan' => $plan->toArray(),
                'creator' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ],
                'request_count' => (int) $row['request_count']
            ];
        }
        
        return $plans;
    }

    /**
     * Find travel plans by customer ID
     */
    public function findByCustomerId(int $customerId): array
    {
        $sql = "SELECT tp.*, c.first_name, c.last_name, c.profile_picture,
                       COUNT(tr.request_id) as request_count
                FROM travel_plans tp 
                JOIN customers c ON tp.customer_id = c.customer_id 
                LEFT JOIN travel_requests tr ON tp.plan_id = tr.plan_id AND tr.status = 'Pending'
                WHERE tp.customer_id = :customer_id
                GROUP BY tp.plan_id 
                ORDER BY tp.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);
        
        $plans = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $plan = new TravelPlan($row);
            $plans[] = [
                'plan' => $plan->toArray(),
                'creator' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ],
                'request_count' => (int) $row['request_count']
            ];
        }
        
        return $plans;
    }

    /**
     * Update travel plan
     */
    public function update(int $planId, array $data): bool
    {
        $sql = "UPDATE travel_plans SET 
                destination = :destination,
                travel_date = :travel_date,
                notes = :notes,
                companions_needed = :companions_needed
                WHERE plan_id = :plan_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'plan_id' => $planId,
            'destination' => $data['destination'],
            'travel_date' => $data['travel_date'],
            'notes' => $data['notes'],
            'companions_needed' => $data['companions_needed']
        ]);
    }

    /**
     * Update companions joined count
     */
    public function updateCompanionsJoined(int $planId, int $companionsJoined): bool
    {
        $sql = "UPDATE travel_plans SET companions_joined = :companions_joined WHERE plan_id = :plan_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'plan_id' => $planId,
            'companions_joined' => $companionsJoined
        ]);
    }

    /**
     * Delete travel plan
     */
    public function delete(int $planId): bool
    {
        $sql = "DELETE FROM travel_plans WHERE plan_id = :plan_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['plan_id' => $planId]);
    }

    /**
     * Count total travel plans
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM travel_plans tp WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['destination'])) {
            $sql .= " AND tp.destination LIKE :destination";
            $params['destination'] = '%' . $filters['destination'] . '%';
        }
        
        if (!empty($filters['travel_date'])) {
            $sql .= " AND tp.travel_date = :travel_date";
            $params['travel_date'] = $filters['travel_date'];
        }
        
        if (!empty($filters['future_only'])) {
            $sql .= " AND tp.travel_date > CURDATE()";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Check if customer owns the plan
     */
    public function isOwner(int $planId, int $customerId): bool
    {
        $sql = "SELECT COUNT(*) FROM travel_plans WHERE plan_id = :plan_id AND customer_id = :customer_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'plan_id' => $planId,
            'customer_id' => $customerId
        ]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get upcoming travel plans (next 30 days)
     */
    public function getUpcoming(int $customerId, int $limit = 5): array
    {
        $sql = "SELECT tp.*, c.first_name, c.last_name, c.profile_picture
                FROM travel_plans tp 
                JOIN customers c ON tp.customer_id = c.customer_id 
                WHERE tp.customer_id = :customer_id 
                AND tp.travel_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY tp.travel_date ASC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $plans = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $plan = new TravelPlan($row);
            $plans[] = [
                'plan' => $plan->toArray(),
                'creator' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        return $plans;
    }
}
