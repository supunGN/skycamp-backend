<?php

namespace App\Repositories;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use PDO;
use Exception;

class WishlistRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get or create wishlist for a customer
     */
    public function getOrCreateWishlist(int $customerId): Wishlist
    {
        // First try to get existing wishlist
        $stmt = $this->pdo->prepare("SELECT * FROM wishlists WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return new Wishlist($data);
        }

        // Create new wishlist if doesn't exist
        $stmt = $this->pdo->prepare("INSERT INTO wishlists (customer_id) VALUES (?)");
        $stmt->execute([$customerId]);

        $wishlistId = $this->pdo->lastInsertId();

        return new Wishlist([
            'wishlist_id' => $wishlistId,
            'customer_id' => $customerId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Add item to wishlist
     */
    public function addItem(int $customerId, string $itemType, int $itemId, array $itemData): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Get or create wishlist
            $wishlist = $this->getOrCreateWishlist($customerId);

            // Check if item already exists in wishlist
            $stmt = $this->pdo->prepare("
                SELECT wishlist_item_id FROM wishlist_items 
                WHERE wishlist_id = ? AND item_type = ? AND item_id = ?
            ");
            $stmt->execute([$wishlist->wishlistId, $itemType, $itemId]);

            if ($stmt->fetch()) {
                $this->pdo->rollBack();
                return false; // Item already exists
            }

            // Add item to wishlist
            $stmt = $this->pdo->prepare("
                INSERT INTO wishlist_items (wishlist_id, item_type, item_id, name, description, image_url, price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $wishlist->wishlistId,
                $itemType,
                $itemId,
                $itemData['name'] ?? '',
                $itemData['description'] ?? null,
                $itemData['image_url'] ?? null,
                $itemData['price'] ?? null
            ]);

            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Remove item from wishlist
     */
    public function removeItem(int $customerId, string $itemType, int $itemId): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Get wishlist
            $wishlist = $this->getOrCreateWishlist($customerId);

            // Remove item
            $stmt = $this->pdo->prepare("
                DELETE FROM wishlist_items 
                WHERE wishlist_id = ? AND item_type = ? AND item_id = ?
            ");

            $result = $stmt->execute([$wishlist->wishlistId, $itemType, $itemId]);

            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Get all items in customer's wishlist
     */
    public function getItems(int $customerId): array
    {
        $wishlist = $this->getOrCreateWishlist($customerId);

        $stmt = $this->pdo->prepare("
            SELECT 
                wi.*,
                CASE 
                    WHEN wi.item_type = 'location' THEN l.type
                    ELSE NULL
                END as location_type
            FROM wishlist_items wi
            LEFT JOIN locations l ON wi.item_type = 'location' AND wi.item_id = l.location_id
            WHERE wi.wishlist_id = ? 
            ORDER BY wi.created_at DESC
        ");
        $stmt->execute([$wishlist->wishlistId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new WishlistItem($row), $results);
    }

    /**
     * Check if item is in wishlist
     */
    public function isItemInWishlist(int $customerId, string $itemType, int $itemId): bool
    {
        $wishlist = $this->getOrCreateWishlist($customerId);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM wishlist_items 
            WHERE wishlist_id = ? AND item_type = ? AND item_id = ?
        ");
        $stmt->execute([$wishlist->wishlistId, $itemType, $itemId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    /**
     * Get wishlist item count
     */
    public function getItemCount(int $customerId): int
    {
        $wishlist = $this->getOrCreateWishlist($customerId);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM wishlist_items 
            WHERE wishlist_id = ?
        ");
        $stmt->execute([$wishlist->wishlistId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Clear entire wishlist
     */
    public function clearWishlist(int $customerId): bool
    {
        try {
            $wishlist = $this->getOrCreateWishlist($customerId);

            $stmt = $this->pdo->prepare("DELETE FROM wishlist_items WHERE wishlist_id = ?");
            return $stmt->execute([$wishlist->wishlistId]);
        } catch (Exception $e) {
            return false;
        }
    }
}
