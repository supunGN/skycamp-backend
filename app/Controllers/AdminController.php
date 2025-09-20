<?php

use App\Services\NotificationService;

class AdminController extends Controller
{
    private PDO $pdo;
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::getConnection();
        $this->notificationService = new NotificationService($this->pdo);
    }

    public function login(Request $request, Response $response): void
    {
        $email = trim((string)$request->json('email'));
        $password = (string)$request->json('password');

        error_log("🔍 Admin Login Attempt - Email: " . $email . ", Password length: " . strlen($password));

        if ($email === '' || $password === '') {
            error_log("❌ Admin Login Failed - Empty email or password");
            $response->error('Email and password are required', 400);
            return;
        }

        // Case-insensitive email match
        $stmt = $this->pdo->prepare('SELECT admin_id, email, password_hash FROM admins WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("🔍 Admin DB Query Result: " . ($row ? "Found admin with ID: " . $row['admin_id'] : "No admin found"));

        if (!$row) {
            error_log("❌ Admin Login Failed - No admin found for email: " . $email);
            $response->json(['success' => false, 'message' => 'Invalid credentials'], 401);
            return;
        }

        $passwordValid = password_verify($password, $row['password_hash']);
        error_log("🔍 Password verification result: " . ($passwordValid ? "VALID" : "INVALID"));
        error_log("🔍 Stored hash: " . substr($row['password_hash'], 0, 20) . "...");

        if (!$passwordValid) {
            error_log("❌ Admin Login Failed - Invalid password for email: " . $email);
            $response->json(['success' => false, 'message' => 'Invalid credentials'], 401);
            return;
        }

        // Set admin session using centralized Session class
        $this->session->setAdmin([
            'admin_id' => $row['admin_id'],
            'email' => $row['email']
        ]);

        error_log("✅ Admin Login Success - Admin ID: " . $row['admin_id'] . ", Email: " . $row['email']);

        $response->json([
            'success' => true,
            'user' => [
                'admin_id' => $row['admin_id'],
                'email' => $row['email'],
            ],
            'redirect_url' => '/admin'
        ], 200);
    }

    public function me(Request $request, Response $response): void
    {
        if (!$this->session->isAdminAuthenticated()) {
            $response->json(['success' => false, 'authenticated' => false], 401);
            return;
        }

        $admin = $this->session->getAdmin();
        $response->json([
            'success' => true,
            'authenticated' => true,
            'user' => $admin
        ], 200);
    }

    public function logout(Request $request, Response $response): void
    {
        $this->session->logout();
        $response->json(['success' => true], 200);
    }

    /**
     * Get all customers
     * GET /api/admin/users/customers
     */
    public function getCustomers(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT c.*, u.email, u.is_active, u.created_at as user_created_at
                FROM customers c
                JOIN users u ON c.user_id = u.user_id
                WHERE u.is_active = 1
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $customers
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching customers: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch customers');
        }
    }

    /**
     * Get all renters
     * GET /api/admin/users/renters
     */
    public function getRenters(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT r.*, u.email, u.is_active, u.created_at as user_created_at
                FROM renters r
                JOIN users u ON r.user_id = u.user_id
                WHERE u.is_active = 1
                ORDER BY r.created_at DESC
            ");
            $stmt->execute();
            $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $renters
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching renters: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch renters');
        }
    }

    /**
     * Get all guides
     * GET /api/admin/users/guides
     */
    public function getGuides(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT g.*, u.email, u.is_active, u.created_at as user_created_at
                FROM guides g
                JOIN users u ON g.user_id = u.user_id
                WHERE u.is_active = 1
                ORDER BY g.created_at DESC
            ");
            $stmt->execute();
            $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $guides
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching guides: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch guides');
        }
    }

    /**
     * Get all suspended users
     * GET /api/admin/users/suspended
     */
    public function getSuspendedUsers(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT su.*, u.email, u.is_active, u.created_at as user_created_at
                FROM suspended_users su
                JOIN users u ON su.user_id = u.user_id
                ORDER BY su.suspended_at DESC
            ");
            $stmt->execute();
            $suspendedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $suspendedUsers
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching suspended users: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch suspended users');
        }
    }

    /**
     * Get all deleted users
     * GET /api/admin/users/deleted
     */
    public function getDeletedUsers(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT 
                    inactive_id as id,
                    user_id,
                    role,
                    email,
                    first_name,
                    last_name,
                    phone_number,
                    reason,
                    deleted_at,
                    deleted_by
                FROM inactive_users
                ORDER BY deleted_at DESC
            ");
            $stmt->execute();
            $deletedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $deletedUsers
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching deleted users: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch deleted users');
        }
    }

    /**
     * Suspend a user
     * POST /api/admin/users/suspend
     */
    public function suspendUser(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $userId = $request->json('user_id');
            $userType = $request->json('user_type'); // 'customer', 'renter', 'guide'
            $reason = $request->json('reason', 'Suspended by admin');

            if (!$userId || !$userType) {
                $response->error('User ID and type are required', 400);
                return;
            }

            $this->pdo->beginTransaction();

            // Update user status to inactive
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Add to suspended_users table
            $stmt = $this->pdo->prepare("
                INSERT INTO suspended_users (user_id, role, reason, suspended_at, suspended_by)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $userId,
                ucfirst($userType), // Capitalize first letter
                $reason,
                $this->session->getAdmin()['admin_id']
            ]);

            // Log the action
            $this->logAdminAction('Suspend', $userId, $userType, $reason);

            $this->pdo->commit();

            $response->json([
                'success' => true,
                'message' => 'User suspended successfully'
            ], 200);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error suspending user: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to suspend user');
        }
    }

    /**
     * Activate a user
     * POST /api/admin/users/activate
     */
    public function activateUser(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $userId = $request->json('user_id');
            $userType = $request->json('user_type');
            $reason = $request->json('reason', 'Activated by admin');

            if (!$userId || !$userType) {
                $response->error('User ID and type are required', 400);
                return;
            }

            $this->pdo->beginTransaction();

            // Update user status to active
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Remove from suspended_users table
            $stmt = $this->pdo->prepare("DELETE FROM suspended_users WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Log the action
            $this->logAdminAction('Activate', $userId, $userType, $reason);

            $this->pdo->commit();

            $response->json([
                'success' => true,
                'message' => 'User activated successfully'
            ], 200);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error activating user: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to activate user');
        }
    }

    /**
     * Delete a user permanently
     * POST /api/admin/users/delete
     */
    public function deleteUser(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $userId = $request->json('user_id');
            $userType = $request->json('user_type');
            $reason = $request->json('reason', 'Deleted by admin');

            if (!$userId || !$userType) {
                $response->error('User ID and type are required', 400);
                return;
            }

            $this->pdo->beginTransaction();

            // Get user data before deletion
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->pdo->rollBack();
                $response->error('User not found', 404);
                return;
            }

            // Move to inactive_users table
            $stmt = $this->pdo->prepare("
                INSERT INTO inactive_users (user_id, role, email, first_name, last_name, phone_number, reason, deleted_at, deleted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $user['user_id'],
                $user['role'],
                $user['email'],
                $user['first_name'],
                $user['last_name'],
                $user['phone_number'],
                $reason,
                $this->session->getAdmin()['admin_id']
            ]);

            // Delete from role-specific table
            $tableMap = [
                'customer' => 'customers',
                'renter' => 'renters',
                'guide' => 'guides'
            ];

            if (isset($tableMap[$userType])) {
                $stmt = $this->pdo->prepare("DELETE FROM {$tableMap[$userType]} WHERE user_id = ?");
                $stmt->execute([$userId]);
            }

            // Delete from users table
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Log the action
            $this->logAdminAction('Delete', $userId, $userType, $reason);

            $this->pdo->commit();

            $response->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error deleting user: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to delete user');
        }
    }

    /**
     * Get admin activity log
     * GET /api/admin/activity-log
     */
    public function getActivityLog(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            // For now, return empty array since we don't have a proper admin activity log table
            // TODO: Create admin_activity_log table for proper logging
            $activityLog = [];

            $response->json([
                'success' => true,
                'data' => $activityLog
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching activity log: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch activity log');
        }
    }

    /**
     * Get all pending verifications
     * GET /api/admin/verifications/pending
     */
    public function getPendingVerifications(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            // Get all users with pending verification status
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.user_id,
                    u.role,
                    u.email,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.first_name
                        WHEN u.role = 'Renter' THEN r.first_name
                        WHEN u.role = 'Guide' THEN g.first_name
                    END as first_name,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.last_name
                        WHEN u.role = 'Renter' THEN r.last_name
                        WHEN u.role = 'Guide' THEN g.last_name
                    END as last_name,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.nic_number
                        WHEN u.role = 'Renter' THEN r.nic_number
                        WHEN u.role = 'Guide' THEN g.nic_number
                    END as nic_number,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.nic_front_image
                        WHEN u.role = 'Renter' THEN r.nic_front_image
                        WHEN u.role = 'Guide' THEN g.nic_front_image
                    END as nic_front_image,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.nic_back_image
                        WHEN u.role = 'Renter' THEN r.nic_back_image
                        WHEN u.role = 'Guide' THEN g.nic_back_image
                    END as nic_back_image,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.verification_status
                        WHEN u.role = 'Renter' THEN r.verification_status
                        WHEN u.role = 'Guide' THEN g.verification_status
                    END as verification_status,
                    uv.status as verification_record_status,
                    uv.created_at as verification_requested_at,
                    uv.note as verification_note,
                    u.created_at
                FROM users u
                LEFT JOIN customers c ON u.user_id = c.user_id AND u.role = 'Customer'
                LEFT JOIN renters r ON u.user_id = r.user_id AND u.role = 'Renter'
                LEFT JOIN guides g ON u.user_id = g.user_id AND u.role = 'Guide'
                LEFT JOIN user_verifications uv ON u.user_id = uv.user_id
                WHERE (
                    (u.role = 'Customer' AND c.verification_status = 'Pending') OR
                    (u.role = 'Renter' AND r.verification_status = 'Pending') OR
                    (u.role = 'Guide' AND g.verification_status = 'Pending')
                )
                AND uv.status = 'Pending'
                ORDER BY uv.created_at ASC
            ");
            $stmt->execute();
            $pendingVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);


            $response->json([
                'success' => true,
                'data' => $pendingVerifications
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching pending verifications: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch pending verifications');
        }
    }

    /**
     * Get all rejected users
     * GET /api/admin/verifications/rejected
     */
    public function getRejectedUsers(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT 
                    uv.verification_id,
                    uv.user_id,
                    uv.reviewed_by as admin_id,
                    uv.note as reason,
                    uv.created_at as rejected_at,
                    u.email,
                    u.role,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.first_name
                        WHEN u.role = 'Renter' THEN r.first_name
                        WHEN u.role = 'Guide' THEN g.first_name
                    END as first_name,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.last_name
                        WHEN u.role = 'Renter' THEN r.last_name
                        WHEN u.role = 'Guide' THEN g.last_name
                    END as last_name,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.nic_number
                        WHEN u.role = 'Renter' THEN r.nic_number
                        WHEN u.role = 'Guide' THEN g.nic_number
                    END as nic_number
                FROM user_verifications uv
                JOIN users u ON uv.user_id = u.user_id
                LEFT JOIN customers c ON u.user_id = c.user_id AND u.role = 'Customer'
                LEFT JOIN renters r ON u.user_id = r.user_id AND u.role = 'Renter'
                LEFT JOIN guides g ON u.user_id = g.user_id AND u.role = 'Guide'
                WHERE uv.status = 'Rejected'
                ORDER BY uv.created_at DESC
            ");
            $stmt->execute();
            $rejectedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $rejectedUsers
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching rejected users: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch rejected users');
        }
    }

    /**
     * Approve user verification
     * POST /api/admin/verifications/approve
     */
    public function approveUser(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $userId = $request->json('user_id');
            $userType = $request->json('user_type');
            $reason = $request->json('reason', 'Approved by admin');

            if (!$userId || !$userType) {
                $response->error('User ID and type are required', 400);
                return;
            }

            $this->pdo->beginTransaction();

            // Update verification status in the appropriate table
            $tableMap = [
                'customer' => 'customers',
                'renter' => 'renters',
                'guide' => 'guides'
            ];

            if (!isset($tableMap[$userType])) {
                $this->pdo->rollBack();
                $response->error('Invalid user type', 400);
                return;
            }

            // Update verification status in the appropriate table
            $stmt = $this->pdo->prepare("UPDATE {$tableMap[$userType]} SET verification_status = 'Yes' WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Update verification record
            $adminId = $this->session->getAdmin()['admin_id'];
            $stmt = $this->pdo->prepare("UPDATE user_verifications SET status = 'Approved', reviewed_by = ?, note = ? WHERE user_id = ?");
            $stmt->execute([$adminId, $reason, $userId]);

            // Log the action
            $this->logVerificationAction('Approved', $userId, $userType, $reason);

            // Send notification to user
            $this->notificationService->sendVerificationNotification($userId, 'approved', $reason);

            $this->pdo->commit();

            $response->json([
                'success' => true,
                'message' => 'User verification approved successfully'
            ], 200);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error approving user verification: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to approve user verification');
        }
    }

    /**
     * Reject user verification
     * POST /api/admin/verifications/reject
     */
    public function rejectUser(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $userId = $request->json('user_id');
            $userType = $request->json('user_type');
            $reason = $request->json('reason', 'Rejected by admin');

            if (!$userId || !$userType) {
                $response->error('User ID and type are required', 400);
                return;
            }

            $this->pdo->beginTransaction();

            // Update verification record
            $adminId = $this->session->getAdmin()['admin_id'];
            $stmt = $this->pdo->prepare("UPDATE user_verifications SET status = 'Rejected', reviewed_by = ?, note = ? WHERE user_id = ?");
            $stmt->execute([$adminId, $reason, $userId]);

            // Update verification status in the appropriate table
            $tableMap = [
                'customer' => 'customers',
                'renter' => 'renters',
                'guide' => 'guides'
            ];

            if (!isset($tableMap[$userType])) {
                $this->pdo->rollBack();
                $response->error('Invalid user type', 400);
                return;
            }

            // Update verification status in the appropriate table
            $stmt = $this->pdo->prepare("UPDATE {$tableMap[$userType]} SET verification_status = 'No' WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Log the rejection action
            $this->logVerificationAction('Rejected', $userId, $userType, $reason);

            // Send notification to user
            $this->notificationService->sendVerificationNotification($userId, 'rejected', $reason);

            $this->pdo->commit();

            $response->json([
                'success' => true,
                'message' => 'User verification rejected successfully'
            ], 200);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error rejecting user verification: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to reject user verification');
        }
    }

    /**
     * Get pending verification count
     * GET /api/admin/verifications/pending-count
     */
    public function getPendingVerificationCount(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            // Count all users with pending verification status
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM users u
                LEFT JOIN customers c ON u.user_id = c.user_id AND u.role = 'Customer'
                LEFT JOIN renters r ON u.user_id = r.user_id AND u.role = 'Renter'
                LEFT JOIN guides g ON u.user_id = g.user_id AND u.role = 'Guide'
                WHERE (
                    (u.role = 'Customer' AND c.verification_status = 'Pending') OR
                    (u.role = 'Renter' AND r.verification_status = 'Pending') OR
                    (u.role = 'Guide' AND g.verification_status = 'Pending')
                )
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'count' => (int)$result['count']
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching pending verification count: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch pending verification count');
        }
    }

    /**
     * Get verification activity log
     * GET /api/admin/verifications/activity-log
     */
    public function getVerificationActivityLog(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();

            $stmt = $this->pdo->prepare("
                SELECT 
                    vml.log_id,
                    vml.target_user_id as user_id,
                    vml.admin_id,
                    vml.action,
                    vml.timestamp as created_at,
                    a.email as admin_email,
                    u.email as user_email,
                    u.role,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.first_name
                        WHEN u.role = 'Renter' THEN r.first_name
                        WHEN u.role = 'Guide' THEN g.first_name
                    END as user_first_name,
                    CASE 
                        WHEN u.role = 'Customer' THEN c.last_name
                        WHEN u.role = 'Renter' THEN r.last_name
                        WHEN u.role = 'Guide' THEN g.last_name
                    END as user_last_name
                FROM verification_management_log vml
                LEFT JOIN admins a ON vml.admin_id = a.admin_id
                LEFT JOIN users u ON vml.target_user_id = u.user_id
                LEFT JOIN customers c ON u.user_id = c.user_id AND u.role = 'Customer'
                LEFT JOIN renters r ON u.user_id = r.user_id AND u.role = 'Renter'
                LEFT JOIN guides g ON u.user_id = g.user_id AND u.role = 'Guide'
                ORDER BY vml.timestamp DESC
            ");
            $stmt->execute();
            $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->json([
                'success' => true,
                'data' => $activityLog
            ], 200);
        } catch (Exception $e) {
            $this->log("Error fetching verification activity log: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to fetch verification activity log');
        }
    }

    /**
     * Log verification action
     */
    private function logVerificationAction(string $action, int $userId, string $userType, string $reason): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO verification_management_log (target_user_id, admin_id, action, timestamp)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $this->session->getAdmin()['admin_id'],
                $action
            ]);
        } catch (Exception $e) {
            $this->log("Error logging verification action: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Log admin action
     */
    private function logAdminAction(string $action, int $userId, string $userType, string $details): void
    {
        try {
            // For now, just log to error log since we don't have a proper admin activity log table
            // TODO: Create admin_activity_log table for proper logging
            $this->log("Admin Action: {$action} on {$userType} user {$userId} - {$details}", 'INFO');
        } catch (Exception $e) {
            $this->log("Error logging admin action: " . $e->getMessage(), 'ERROR');
        }
    }
}
