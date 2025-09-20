<?php

use App\Repositories\WishlistRepository;

class WishlistController extends Controller
{
    private WishlistRepository $wishlistRepository;
    private PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getConnection();
        $this->wishlistRepository = new WishlistRepository($this->pdo);
    }

    /**
     * Get user's wishlist items
     * GET /api/wishlist
     */
    public function getWishlist(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            // Get customer ID from user
            $customerId = $this->getCustomerId($userId);
            if (!$customerId) {
                $response->error('Customer profile not found', 404);
                return;
            }

            $items = $this->wishlistRepository->getItems($customerId);

            $response->json([
                'success' => true,
                'data' => array_map(fn($item) => $item->toArray(), $items)
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching wishlist: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch wishlist');
        }
    }

    /**
     * Add item to wishlist
     * POST /api/wishlist/add
     */
    public function addItem(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            $itemType = $request->json('item_type');
            $itemId = (int)$request->json('item_id');
            $itemData = $request->json('item_data', []);

            if (!$itemType || !$itemId) {
                $response->error('Item type and ID are required', 400);
                return;
            }

            // Get customer ID from user
            $customerId = $this->getCustomerId($userId);
            if (!$customerId) {
                $response->error('Customer profile not found', 404);
                return;
            }

            $success = $this->wishlistRepository->addItem($customerId, $itemType, $itemId, $itemData);

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Item added to wishlist'
                ], 200);
            } else {
                $response->error('Item already in wishlist or failed to add', 400);
            }
        } catch (Exception $e) {
            $this->log("Error adding item to wishlist: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to add item to wishlist');
        }
    }

    /**
     * Remove item from wishlist
     * POST /api/wishlist/remove
     */
    public function removeItem(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            $itemType = $request->json('item_type');
            $itemId = (int)$request->json('item_id');

            if (!$itemType || !$itemId) {
                $response->error('Item type and ID are required', 400);
                return;
            }

            // Get customer ID from user
            $customerId = $this->getCustomerId($userId);
            if (!$customerId) {
                $response->error('Customer profile not found', 404);
                return;
            }

            $success = $this->wishlistRepository->removeItem($customerId, $itemType, $itemId);

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Item removed from wishlist'
                ], 200);
            } else {
                $response->error('Failed to remove item from wishlist', 400);
            }
        } catch (Exception $e) {
            $this->log("Error removing item from wishlist: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to remove item from wishlist');
        }
    }

    /**
     * Check if item is in wishlist
     * GET /api/wishlist/check
     */
    public function checkItem(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            $itemType = $request->get('item_type');
            $itemId = (int)$request->get('item_id');

            if (!$itemType || !$itemId) {
                $response->error('Item type and ID are required', 400);
                return;
            }

            // Get customer ID from user
            $customerId = $this->getCustomerId($userId);
            if (!$customerId) {
                $response->error('Customer profile not found', 404);
                return;
            }

            $isInWishlist = $this->wishlistRepository->isItemInWishlist($customerId, $itemType, $itemId);

            $response->json([
                'success' => true,
                'in_wishlist' => $isInWishlist
            ], 200);
        } catch (Exception $e) {
            $this->log("Error checking wishlist item: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to check wishlist item');
        }
    }

    /**
     * Get wishlist item count
     * GET /api/wishlist/count
     */
    public function getItemCount(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            // Get customer ID from user
            $customerId = $this->getCustomerId($userId);
            if (!$customerId) {
                $response->error('Customer profile not found', 404);
                return;
            }

            $count = $this->wishlistRepository->getItemCount($customerId);

            $response->json([
                'success' => true,
                'count' => $count
            ], 200);
        } catch (Exception $e) {
            $this->log("Error getting wishlist count: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to get wishlist count');
        }
    }

    /**
     * Clear entire wishlist
     * POST /api/wishlist/clear
     */
    public function clearWishlist(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            // Get customer ID from user
            $customerId = $this->getCustomerId($userId);
            if (!$customerId) {
                $response->error('Customer profile not found', 404);
                return;
            }

            $success = $this->wishlistRepository->clearWishlist($customerId);

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Wishlist cleared successfully'
                ], 200);
            } else {
                $response->error('Failed to clear wishlist', 400);
            }
        } catch (Exception $e) {
            $this->log("Error clearing wishlist: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to clear wishlist');
        }
    }

    /**
     * Get customer ID from user ID
     */
    private function getCustomerId(int $userId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['customer_id'] : null;
    }
}
