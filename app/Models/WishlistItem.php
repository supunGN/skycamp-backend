<?php

namespace App\Models;

class WishlistItem
{
    public int $wishlistItemId;
    public int $wishlistId;
    public string $itemType; // 'equipment', 'location', 'guide'
    public int $itemId;
    public string $name;
    public ?string $description;
    public ?string $imageUrl;
    public ?float $price;
    public ?string $locationType; // 'Camping' or 'Stargazing' for location items
    public string $createdAt;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->wishlistItemId = (int)($data['wishlist_item_id'] ?? 0);
            $this->wishlistId = (int)($data['wishlist_id'] ?? 0);
            $this->itemType = $data['item_type'] ?? '';
            $this->itemId = (int)($data['item_id'] ?? 0);
            $this->name = $data['name'] ?? '';
            $this->description = $data['description'] ?? null;
            $this->imageUrl = $data['image_url'] ?? null;
            $this->price = $data['price'] ? (float)$data['price'] : null;
            $this->locationType = $data['location_type'] ?? null;
            $this->createdAt = $data['created_at'] ?? '';
        }
    }

    public function toArray(): array
    {
        return [
            'wishlist_item_id' => $this->wishlistItemId,
            'wishlist_id' => $this->wishlistId,
            'item_type' => $this->itemType,
            'item_id' => $this->itemId,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'price' => $this->price,
            'location_type' => $this->locationType,
            'created_at' => $this->createdAt,
        ];
    }
}
