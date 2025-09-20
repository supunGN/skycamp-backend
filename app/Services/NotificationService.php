<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use PDO;

class NotificationService
{
    private NotificationRepository $notificationRepository;

    public function __construct(PDO $pdo)
    {
        $this->notificationRepository = new NotificationRepository($pdo);
    }

    /**
     * Send verification notification to user
     */
    public function sendVerificationNotification(int $userId, string $status, string $reason = null): bool
    {
        $message = $this->getVerificationMessage($status, $reason);

        return $this->notificationRepository->create([
            'user_id' => $userId,
            'type' => 'Verification',
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Get appropriate message based on verification status
     */
    private function getVerificationMessage(string $status, string $reason = null): string
    {
        switch ($status) {
            case 'approved':
                return '🎉 Congratulations! Your identity verification has been approved. You now have access to all verified user features.';

            case 'rejected':
                $baseMessage = '❌ Your identity verification request has been rejected.';
                if ($reason) {
                    $baseMessage .= " Reason: {$reason}";
                }
                $baseMessage .= ' Please review your documents and resubmit for verification.';
                return $baseMessage;

            case 'pending':
                return '⏳ Your identity verification request has been submitted and is under review. This usually takes 24-48 hours.';

            default:
                return 'Your verification status has been updated.';
        }
    }

    /**
     * Send travel buddy notification
     */
    public function sendTravelBuddyNotification(int $userId, string $type, array $data = []): bool
    {
        $message = $this->getTravelBuddyMessage($type, $data);

        return $this->notificationRepository->create([
            'user_id' => $userId,
            'type' => 'TravelBuddyRequest',
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Get travel buddy notification message
     */
    private function getTravelBuddyMessage(string $type, array $data): string
    {
        switch ($type) {
            case 'request_received':
                $buddyName = $data['buddy_name'] ?? 'Someone';
                return "🤝 {$buddyName} wants to connect with you as a Travel Buddy!";

            case 'request_accepted':
                $buddyName = $data['buddy_name'] ?? 'Your buddy';
                return "✅ {$buddyName} has accepted your Travel Buddy request!";

            case 'request_rejected':
                $buddyName = $data['buddy_name'] ?? 'Your buddy';
                return "❌ {$buddyName} has declined your Travel Buddy request.";

            default:
                return 'You have a new Travel Buddy notification.';
        }
    }

    /**
     * Send booking notification
     */
    public function sendBookingNotification(int $userId, string $type, array $data = []): bool
    {
        $message = $this->getBookingMessage($type, $data);

        return $this->notificationRepository->create([
            'user_id' => $userId,
            'type' => 'PaymentSuccess',
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Get booking notification message
     */
    private function getBookingMessage(string $type, array $data): string
    {
        switch ($type) {
            case 'booking_confirmed':
                $serviceName = $data['service_name'] ?? 'your booking';
                return "✅ Your booking for {$serviceName} has been confirmed!";

            case 'booking_cancelled':
                $serviceName = $data['service_name'] ?? 'your booking';
                return "❌ Your booking for {$serviceName} has been cancelled.";

            case 'payment_success':
                $amount = $data['amount'] ?? 'your payment';
                return "💳 Payment of {$amount} has been processed successfully!";

            default:
                return 'You have a new booking notification.';
        }
    }

    /**
     * Get notifications for user
     */
    public function getUserNotifications(int $userId, int $limit = 50): array
    {
        return $this->notificationRepository->getByUserId($userId, $limit);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->notificationRepository->markAsRead($notificationId, $userId);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }

    /**
     * Send equipment added to cart notification to renter
     */
    public function sendEquipmentAddedToCartNotification(int $renterUserId, array $data): bool
    {
        $customerName = $data['customer_name'] ?? 'A customer';
        $equipmentName = $data['equipment_name'] ?? 'equipment';
        $quantity = $data['quantity'] ?? 1;

        $message = "🛒 {$customerName} added {$quantity}x {$equipmentName} to their cart. Quantity reserved until checkout.";

        return $this->notificationRepository->create([
            'user_id' => $renterUserId,
            'type' => 'EquipmentAddedToCart',
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Send booking created notification to renter
     */
    public function sendBookingCreatedNotification(int $renterUserId, array $data): bool
    {
        $customerName = $data['customer_name'] ?? 'A customer';
        $equipmentName = $data['equipment_name'] ?? 'equipment';
        $totalAmount = $data['total_amount'] ?? '0';
        $bookingId = $data['booking_id'] ?? '';

        $message = "📋 New booking #{$bookingId} from {$customerName} for {$equipmentName}. Total: Rs. {$totalAmount}";

        return $this->notificationRepository->create([
            'user_id' => $renterUserId,
            'type' => 'BookingCreated',
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Send booking completed notification to renter
     */
    public function sendBookingCompletedNotification(int $renterUserId, array $data): bool
    {
        $customerName = $data['customer_name'] ?? 'A customer';
        $equipmentName = $data['equipment_name'] ?? 'equipment';
        $bookingId = $data['booking_id'] ?? '';

        $message = "✅ Booking #{$bookingId} completed! {$customerName} has marked {$equipmentName} as received.";

        return $this->notificationRepository->create([
            'user_id' => $renterUserId,
            'type' => 'BookingCompleted',
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
