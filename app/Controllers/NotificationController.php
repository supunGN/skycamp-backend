<?php

use App\Services\NotificationService;

class NotificationController extends Controller
{
    private NotificationService $notificationService;
    private PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getConnection();
        $this->notificationService = new NotificationService($this->pdo);
    }

    /**
     * Get user notifications
     * GET /api/notifications
     */
    public function getUserNotifications(Request $request, Response $response): void
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

            $limit = (int)($request->get('limit') ?? 50);
            $notifications = $this->notificationService->getUserNotifications($userId, $limit);

            $response->json([
                'success' => true,
                'data' => array_map(fn($notification) => $notification->toArray(), $notifications)
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching user notifications: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch notifications');
        }
    }

    /**
     * Get unread notification count
     * GET /api/notifications/unread-count
     */
    public function getUnreadCount(Request $request, Response $response): void
    {
        try {
            $userId = $this->session->get('user_id');

            // If no session, try to establish session from request headers (for localStorage-based auth)
            if (!$userId) {
                // Check for user ID in headers (from localStorage)
                $userId = $request->header('x-user-id');
                $userRole = $request->header('x-user-role');

                if ($userId) {
                    // Verify the user exists
                    $userStmt = $this->pdo->prepare("
                        SELECT user_id, role FROM users WHERE user_id = ?
                    ");
                    $userStmt->execute([$userId]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $userId = null;
                    }
                }
            }

            if (!$userId) {
                $response->setStatusCode(401);
                $response->json([
                    'success' => false,
                    'message' => 'Please log in'
                ]);
                return;
            }

            $count = $this->notificationService->getUnreadCount($userId);

            $response->json([
                'success' => true,
                'count' => $count
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching unread count: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch unread count');
        }
    }

    /**
     * Mark notification as read
     * POST /api/notifications/mark-read
     */
    public function markAsRead(Request $request, Response $response): void
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

            $notificationId = (int)$request->json('notification_id');

            if (!$notificationId) {
                $response->error('Notification ID is required', 400);
                return;
            }

            $success = $this->notificationService->markAsRead($notificationId, $userId);

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ], 200);
            } else {
                $response->error('Failed to mark notification as read', 400);
            }
        } catch (Exception $e) {
            $this->log("Error marking notification as read: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to mark notification as read');
        }
    }

    /**
     * Mark all notifications as read
     * POST /api/notifications/mark-all-read
     */
    public function markAllAsRead(Request $request, Response $response): void
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

            $success = $this->notificationService->markAllAsRead($userId);

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'All notifications marked as read'
                ], 200);
            } else {
                $response->error('Failed to mark all notifications as read', 400);
            }
        } catch (Exception $e) {
            $this->log("Error marking all notifications as read: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to mark all notifications as read');
        }
    }
}
