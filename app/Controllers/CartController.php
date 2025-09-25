<?php

/**
 * Cart Controller
 * Handles cart-related API endpoints
 */

class CartController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get or create cart for current customer
     */
    public function getOrCreateCart(Request $request, Response $response): void
    {
        try {
            $customerId = $this->session->get('user_id');

            if (!$customerId) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer not logged in'
                ], 401);
                return;
            }

            $pdo = Database::getConnection();

            // Convert user_id to customer_id
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer profile not found'
                ], 400);
                return;
            }

            $customerId = $customer['customer_id'];

            // Clean up expired carts first
            $this->cleanExpiredCarts($pdo);

            // Check if customer has an active cart
            $stmt = $pdo->prepare("
                SELECT c.*, r.first_name, r.last_name, r.district 
                FROM carts c 
                LEFT JOIN renters r ON c.renter_id = r.renter_id
                WHERE c.customer_id = ? AND c.status = 'Active' 
                AND (c.expires_at IS NULL OR c.expires_at > NOW())
                ORDER BY c.created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$customerId]);
            $cart = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cart) {
                // No active cart found
                $response->json([
                    'success' => true,
                    'data' => null
                ], 200);
                return;
            }

            // Get cart items with equipment details
            $stmt = $pdo->prepare("
                SELECT 
                    ci.*,
                    e.equipment_id,
                    e.name as equipment_name,
                    e.description,
                    re.price_per_day,
                    re.item_condition,
                    re.stock_quantity,
                    rep.photo_path,
                    r.first_name as renter_first_name,
                    r.last_name as renter_last_name,
                    r.district as renter_location
                FROM cartitems ci
                JOIN renterequipment re ON ci.renter_equipment_id = re.renter_equipment_id
                JOIN equipment e ON re.equipment_id = e.equipment_id
                LEFT JOIN (
                    SELECT renter_equipment_id, photo_path, 
                           ROW_NUMBER() OVER (PARTITION BY renter_equipment_id ORDER BY COALESCE(display_order, 1)) as rn
                    FROM renterequipmentphotos
                ) rep ON re.renter_equipment_id = rep.renter_equipment_id AND rep.rn = 1
                JOIN renters r ON re.renter_id = r.renter_id
                WHERE ci.cart_id = ?
                ORDER BY ci.cart_item_id
            ");
            $stmt->execute([$cart['cart_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format items
            $formattedItems = [];
            foreach ($items as $item) {
                $image = '/default-equipment.png'; // Default fallback
                if ($item['photo_path']) {
                    $image = 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . $item['photo_path'];
                }

                $formattedItems[] = [
                    'id' => $item['cart_item_id'],
                    'renterEquipmentId' => $item['renter_equipment_id'],
                    'equipmentId' => $item['equipment_id'],
                    'name' => $item['equipment_name'],
                    'description' => $item['description'],
                    'price' => floatval($item['price_per_day']),
                    'quantity' => intval($item['quantity']),
                    'image' => $image,
                    'condition' => $item['item_condition'],
                    'stockQuantity' => intval($item['stock_quantity']),
                    'renterName' => trim($item['renter_first_name'] . ' ' . $item['renter_last_name']),
                    'renterLocation' => $item['renter_location']
                ];
            }

            $response->json([
                'success' => true,
                'data' => [
                    'cartId' => $cart['cart_id'],
                    'renterId' => $cart['renter_id'],
                    'renterName' => $cart['renter_name'] ?? trim($cart['first_name'] . ' ' . $cart['last_name']),
                    'renterLocation' => $cart['district'],
                    'startDate' => $cart['start_date'],
                    'endDate' => $cart['end_date'],
                    'expiresAt' => $cart['expires_at'],
                    'items' => $formattedItems
                ]
            ], 200);
        } catch (\Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch cart'
            ], 500);
        }
    }

    /**
     * Create new cart with items
     */
    public function createCart(Request $request, Response $response): void
    {
        try {
            $customerId = $this->session->get('user_id');


            if (!$customerId) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer not logged in'
                ], 401);
                return;
            }

            $data = $request->json();
            $renterId = $data['renterId'] ?? null;
            $items = $data['items'] ?? [];
            $startDate = $data['startDate'] ?? null;
            $endDate = $data['endDate'] ?? null;

            error_log("CartController - createCart received dates: startDate={$startDate}, endDate={$endDate}");


            if (!$renterId || empty($items) || !$startDate || !$endDate) {
                $response->json([
                    'success' => false,
                    'message' => 'Missing required cart data'
                ], 400);
                return;
            }

            // Validate date format (should be YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                error_log("CartController - Invalid date format: startDate={$startDate}, endDate={$endDate}");
                $response->json([
                    'success' => false,
                    'message' => "Invalid date format. Expected YYYY-MM-DD. Received: startDate={$startDate}, endDate={$endDate}"
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Convert user_id to customer_id
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer profile not found'
                ], 400);
                return;
            }

            $customerId = $customer['customer_id'];

            // Clean up expired carts first
            $this->cleanExpiredCarts($pdo);

            $pdo->beginTransaction();

            try {
                // Check if customer already has an active cart
                $stmt = $pdo->prepare("
                    SELECT cart_id FROM carts 
                    WHERE customer_id = ? AND status = 'Active' 
                    AND (expires_at IS NULL OR expires_at > NOW())
                ");
                $stmt->execute([$customerId]);
                $existingCart = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingCart) {
                    // Clear existing cart
                    $this->clearCart($existingCart['cart_id'], $pdo);
                }

                // Get renter info
                $stmt = $pdo->prepare("
                    SELECT first_name, last_name, district 
                    FROM renters WHERE renter_id = ?
                ");
                $stmt->execute([$renterId]);
                $renter = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$renter) {
                    throw new \Exception('Renter not found');
                }

                // Create new cart
                $stmt = $pdo->prepare("
                    INSERT INTO carts (customer_id, renter_id, renter_name, start_date, end_date, expires_at, status) 
                    VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 'Active')
                ");
                $renterName = trim($renter['first_name'] . ' ' . $renter['last_name']);
                $stmt->execute([$customerId, $renterId, $renterName, $startDate, $endDate]);
                $cartId = $pdo->lastInsertId();

                // Add items to cart
                foreach ($items as $item) {
                    $this->addItemToCart($cartId, $item, $pdo);
                }

                $pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Cart created successfully',
                    'data' => ['cartId' => $cartId]
                ], 201);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("Create cart error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to create cart'
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItemQuantity(Request $request, Response $response): void
    {
        try {
            $customerId = $this->session->get('user_id');

            if (!$customerId) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer not logged in'
                ], 401);
                return;
            }

            $pdo = Database::getConnection();

            // Convert user_id to customer_id
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer profile not found'
                ], 400);
                return;
            }

            $customerId = $customer['customer_id'];

            $data = $request->json();
            $cartItemId = $data['cartItemId'] ?? null;
            $quantity = $data['quantity'] ?? null;

            if (!$cartItemId || !$quantity || $quantity < 1) {
                $response->json([
                    'success' => false,
                    'message' => 'Invalid quantity'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Clean up expired carts first
            $this->cleanExpiredCarts($pdo);

            $pdo->beginTransaction();

            try {
                // Verify cart ownership and get item details
                $stmt = $pdo->prepare("
                    SELECT ci.*, re.stock_quantity, c.customer_id, c.expires_at
                    FROM cartitems ci
                    JOIN renterequipment re ON ci.renter_equipment_id = re.renter_equipment_id
                    JOIN carts c ON ci.cart_id = c.cart_id
                    WHERE ci.cart_item_id = ? AND c.customer_id = ? AND c.status = 'Active'
                    AND (c.expires_at IS NULL OR c.expires_at > NOW())
                ");
                $stmt->execute([$cartItemId, $customerId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new \Exception('Cart item not found or expired');
                }

                // Check available stock (excluding current cart item)
                $availableStock = $this->getAvailableStockForUpdate($item['renter_equipment_id'], $item['cart_item_id'], $pdo);

                if ($quantity > $availableStock) {
                    throw new \Exception('Insufficient stock available');
                }

                // Update quantity
                $stmt = $pdo->prepare("
                    UPDATE cartitems 
                    SET quantity = ? 
                    WHERE cart_item_id = ?
                ");
                $stmt->execute([$quantity, $cartItemId]);

                $pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Quantity updated successfully'
                ], 200);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("Update quantity error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, Response $response): void
    {
        try {
            $customerId = $this->session->get('user_id');

            if (!$customerId) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer not logged in'
                ], 401);
                return;
            }

            $pdo = Database::getConnection();

            // Convert user_id to customer_id
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer profile not found'
                ], 400);
                return;
            }

            $customerId = $customer['customer_id'];

            $data = $request->json();
            $cartItemId = $data['cartItemId'] ?? null;

            if (!$cartItemId) {
                $response->json([
                    'success' => false,
                    'message' => 'Cart item ID required'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Clean up expired carts first
            $this->cleanExpiredCarts($pdo);

            $pdo->beginTransaction();

            try {
                // Verify cart ownership
                $stmt = $pdo->prepare("
                    SELECT ci.*, c.customer_id, c.expires_at
                    FROM cartitems ci
                    JOIN carts c ON ci.cart_id = c.cart_id
                    WHERE ci.cart_item_id = ? AND c.customer_id = ? AND c.status = 'Active'
                    AND (c.expires_at IS NULL OR c.expires_at > NOW())
                ");
                $stmt->execute([$cartItemId, $customerId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new \Exception('Cart item not found or expired');
                }

                // Remove item
                $stmt = $pdo->prepare("DELETE FROM cartitems WHERE cart_item_id = ?");
                $stmt->execute([$cartItemId]);

                // Check if cart is empty
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cartitems WHERE cart_id = ?");
                $stmt->execute([$item['cart_id']]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($count == 0) {
                    // Clear empty cart
                    $this->clearCart($item['cart_id'], $pdo);
                }

                $pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Item removed successfully'
                ], 200);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("Remove item error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Clear cart and release all holds
     */
    private function clearCart($cartId, $pdo): void
    {
        // Delete cart items
        $stmt = $pdo->prepare("DELETE FROM cartitems WHERE cart_id = ?");
        $stmt->execute([$cartId]);

        // Mark cart as expired
        $stmt = $pdo->prepare("UPDATE carts SET status = 'Expired' WHERE cart_id = ?");
        $stmt->execute([$cartId]);
    }

    /**
     * Add item to cart with stock validation
     */
    private function addItemToCart($cartId, $item, $pdo): void
    {
        $renterEquipmentId = $item['renterEquipmentId'] ?? null;
        $quantity = $item['quantity'] ?? 1;

        if (!$renterEquipmentId) {
            throw new \Exception('Invalid item data');
        }

        // Check available stock
        $availableStock = $this->getAvailableStock($renterEquipmentId, $pdo);

        if ($quantity > $availableStock) {
            throw new \Exception('Insufficient stock available');
        }

        // Add to cart
        $stmt = $pdo->prepare("
            INSERT INTO cartitems (cart_id, renter_equipment_id, quantity, price_per_day, is_reserved) 
            VALUES (?, ?, ?, (SELECT price_per_day FROM renterequipment WHERE renter_equipment_id = ?), 1)
        ");
        $stmt->execute([$cartId, $renterEquipmentId, $quantity, $renterEquipmentId]);
    }

    /**
     * Calculate available stock (total - reserved - booked)
     */
    private function getAvailableStock($renterEquipmentId, $pdo): int
    {
        // Get total stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM renterequipment WHERE renter_equipment_id = ?");
        $stmt->execute([$renterEquipmentId]);
        $totalStock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];

        // Get reserved quantity (from active carts)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0) as reserved
            FROM cartitems ci
            JOIN carts c ON ci.cart_id = c.cart_id
            WHERE ci.renter_equipment_id = ? AND c.status = 'Active'
            AND (c.expires_at IS NULL OR c.expires_at > NOW())
        ");
        $stmt->execute([$renterEquipmentId]);
        $reserved = $stmt->fetch(PDO::FETCH_ASSOC)['reserved'];

        // Get booked quantity (from confirmed bookings)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(er.quantity), 0) as booked
            FROM equipment_reservations er
            WHERE er.renter_equipment_id = ? AND er.status = 'Booked'
        ");
        $stmt->execute([$renterEquipmentId]);
        $booked = $stmt->fetch(PDO::FETCH_ASSOC)['booked'];

        return max(0, $totalStock - $reserved - $booked);
    }

    /**
     * Calculate available stock for quantity update (excludes current cart item)
     */
    private function getAvailableStockForUpdate($renterEquipmentId, $cartItemId, $pdo): int
    {
        // Get total stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM renterequipment WHERE renter_equipment_id = ?");
        $stmt->execute([$renterEquipmentId]);
        $totalStock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];

        // Get reserved quantity (from active carts, excluding current cart item)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0) as reserved
            FROM cartitems ci
            JOIN carts c ON ci.cart_id = c.cart_id
            WHERE ci.renter_equipment_id = ? AND ci.cart_item_id != ? AND c.status = 'Active'
            AND (c.expires_at IS NULL OR c.expires_at > NOW())
        ");
        $stmt->execute([$renterEquipmentId, $cartItemId]);
        $reserved = $stmt->fetch(PDO::FETCH_ASSOC)['reserved'];

        // Get booked quantity (from confirmed bookings)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(er.quantity), 0) as booked
            FROM equipment_reservations er
            WHERE er.renter_equipment_id = ? AND er.status = 'Booked'
        ");
        $stmt->execute([$renterEquipmentId]);
        $booked = $stmt->fetch(PDO::FETCH_ASSOC)['booked'];

        return max(0, $totalStock - $reserved - $booked);
    }

    /**
     * Clean up expired carts and release their holds
     */
    private function cleanExpiredCarts(PDO $pdo): void
    {
        try {
            // Find expired carts
            $stmt = $pdo->prepare("
                SELECT cart_id FROM carts 
                WHERE status = 'Active' AND expires_at < NOW()
            ");
            $stmt->execute();
            $expiredCarts = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($expiredCarts)) {
                // Clear expired cart items
                $placeholders = implode(',', array_fill(0, count($expiredCarts), '?'));
                $stmt = $pdo->prepare("DELETE FROM cartitems WHERE cart_id IN ($placeholders)");
                $stmt->execute($expiredCarts);

                // Mark expired carts as expired
                $stmt = $pdo->prepare("UPDATE carts SET status = 'Expired' WHERE cart_id IN ($placeholders)");
                $stmt->execute($expiredCarts);

                error_log("Cleaned up " . count($expiredCarts) . " expired carts");
            }
        } catch (\Exception $e) {
            error_log("Error cleaning expired carts: " . $e->getMessage());
        }
    }
}
