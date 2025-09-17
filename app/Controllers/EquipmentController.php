<?php

/**
 * Equipment Controller
 * Handles equipment-related API endpoints
 */

class EquipmentController extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getConnection();
    }

    /**
     * Get all equipment categories with their equipment items
     */
    public function getCategoriesWithEquipment(Request $request, Response $response): void
    {
        try {
            $sql = "SELECT 
                        ec.category_id,
                        ec.type,
                        ec.name as category_name,
                        ec.description as category_description,
                        ec.created_at as category_created_at,
                        e.equipment_id,
                        e.name as equipment_name,
                        e.description as equipment_description,
                        e.status as equipment_status,
                        e.created_at as equipment_created_at
                    FROM equipment_categories ec
                    LEFT JOIN equipment e ON ec.category_id = e.category_id AND e.status = 'Active'
                    WHERE ec.category_id IS NOT NULL
                    ORDER BY ec.category_id, e.equipment_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group equipment by category
            $categories = [];
            foreach ($results as $row) {
                $categoryId = $row['category_id'];

                if (!isset($categories[$categoryId])) {
                    $categories[$categoryId] = [
                        'categoryId' => $row['category_id'],
                        'type' => $row['type'],
                        'name' => $row['category_name'],
                        'description' => $row['category_description'],
                        'createdAt' => $row['category_created_at'],
                        'equipment' => []
                    ];
                }

                // Add equipment item if it exists
                if ($row['equipment_id']) {
                    $categories[$categoryId]['equipment'][] = [
                        'equipmentId' => $row['equipment_id'],
                        'name' => $row['equipment_name'],
                        'description' => $row['equipment_description'],
                        'status' => $row['equipment_status'],
                        'createdAt' => $row['equipment_created_at']
                    ];
                }
            }

            // Convert to indexed array
            $categoriesArray = array_values($categories);

            $response->json([
                'success' => true,
                'data' => $categoriesArray
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching equipment categories: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch equipment categories'
            ], 500);
        }
    }

    /**
     * Get all equipment categories only
     */
    public function getCategories(Request $request, Response $response): void
    {
        try {
            $sql = "SELECT 
                        category_id,
                        type,
                        name,
                        description,
                        created_at
                    FROM equipment_categories
                    ORDER BY category_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the response
            $formattedCategories = array_map(function ($category) {
                return [
                    'categoryId' => $category['category_id'],
                    'type' => $category['type'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'createdAt' => $category['created_at']
                ];
            }, $categories);

            $response->json([
                'success' => true,
                'data' => $formattedCategories
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching equipment categories: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch equipment categories'
            ], 500);
        }
    }

    /**
     * Get equipment by category
     */
    public function getEquipmentByCategory(Request $request, Response $response): void
    {
        try {
            $categoryId = $request->get('categoryId');

            if (!$categoryId) {
                $response->json([
                    'success' => false,
                    'message' => 'Category ID is required'
                ], 400);
                return;
            }

            $sql = "SELECT 
                        equipment_id,
                        category_id,
                        name,
                        description,
                        status,
                        created_at
                    FROM equipment
                    WHERE category_id = ? AND status = 'Active'
                    ORDER BY equipment_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$categoryId]);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the response
            $formattedEquipment = array_map(function ($item) {
                return [
                    'equipmentId' => $item['equipment_id'],
                    'categoryId' => $item['category_id'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'status' => $item['status'],
                    'createdAt' => $item['created_at']
                ];
            }, $equipment);

            $response->json([
                'success' => true,
                'data' => $formattedEquipment
            ], 200);
        } catch (Exception $e) {
            error_log("Error fetching equipment by category: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch equipment'
            ], 500);
        }
    }
}
