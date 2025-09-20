<?php

namespace App\Models;

class Wishlist
{
    public int $wishlistId;
    public int $customerId;
    public string $createdAt;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->wishlistId = (int)($data['wishlist_id'] ?? 0);
            $this->customerId = (int)($data['customer_id'] ?? 0);
            $this->createdAt = $data['created_at'] ?? '';
        }
    }

    public function toArray(): array
    {
        return [
            'wishlist_id' => $this->wishlistId,
            'customer_id' => $this->customerId,
            'created_at' => $this->createdAt,
        ];
    }
}
