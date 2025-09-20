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
            error_log("=== getPendingVerifications START ===");

            // Get all users with pending verification status - step by step approach
            $allPending = [];

            // Get pending customers
            try {
                error_log("Fetching pending customers...");
                $stmt = $this->pdo->prepare("
                SELECT 
                    u.user_id,
                    u.role,
                    u.email,
                        c.first_name,
                        c.last_name,
                        c.nic_number,
                        c.nic_front_image,
                        c.nic_back_image,
                        c.verification_status,
                        c.updated_at as verification_requested_at,
                    u.created_at
                FROM users u
                    INNER JOIN customers c ON u.user_id = c.user_id
                    WHERE u.role = 'Customer' AND c.verification_status = 'Pending'
                    ORDER BY c.updated_at DESC
            ");
                $stmt->execute();
                $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $allPending = array_merge($allPending, $customers);
                error_log("Found " . count($customers) . " pending customers");
                error_log("Customer data: " . json_encode($customers));
            } catch (Exception $e) {
                error_log("Error fetching pending customers: " . $e->getMessage());
                error_log("Customer query error trace: " . $e->getTraceAsString());
            }

            // Get pending renters
            try {
                error_log("Fetching pending renters...");
                $stmt = $this->pdo->prepare("
                    SELECT 
                        u.user_id,
                        u.role,
                        u.email,
                        r.first_name,
                        r.last_name,
                        r.nic_number,
                        r.nic_front_image,
                        r.nic_back_image,
                        r.verification_status,
                        r.created_at as verification_requested_at,
                        u.created_at
                    FROM users u
                    INNER JOIN renters r ON u.user_id = r.user_id
                    WHERE u.role = 'Renter' AND r.verification_status = 'Pending'
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute();
                $renters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $allPending = array_merge($allPending, $renters);
                error_log("Found " . count($renters) . " pending renters");
                error_log("Renter data: " . json_encode($renters));
            } catch (Exception $e) {
                error_log("Error fetching pending renters: " . $e->getMessage());
                error_log("Renter query error trace: " . $e->getTraceAsString());
            }

            // Get pending guides
            try {
                error_log("Fetching pending guides...");
                $stmt = $this->pdo->prepare("
                    SELECT 
                        u.user_id,
                        u.role,
                        u.email,
                        g.first_name,
                        g.last_name,
                        g.nic_number,
                        g.nic_front_image,
                        g.nic_back_image,
                        g.verification_status,
                        g.created_at as verification_requested_at,
                        u.created_at
                    FROM users u
                    INNER JOIN guides g ON u.user_id = g.user_id
                    WHERE u.role = 'Guide' AND g.verification_status = 'Pending'
                    ORDER BY g.created_at DESC
                ");
                $stmt->execute();
                $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $allPending = array_merge($allPending, $guides);
                error_log("Found " . count($guides) . " pending guides");
                error_log("Guide data: " . json_encode($guides));
            } catch (Exception $e) {
                error_log("Error fetching pending guides: " . $e->getMessage());
                error_log("Guide query error trace: " . $e->getTraceAsString());
            }

            // Sort by verification_requested_at (fallback to created_at if not available)
            usort($allPending, function ($a, $b) {
                $timeA = $a['verification_requested_at'] ? strtotime($a['verification_requested_at']) : strtotime($a['created_at']);
                $timeB = $b['verification_requested_at'] ? strtotime($b['verification_requested_at']) : strtotime($b['created_at']);
                return $timeB - $timeA;
            });

            error_log("Total pending verifications: " . count($allPending));
            error_log("Final merged data: " . json_encode($allPending));

            $response->json([
                'success' => true,
                'data' => $allPending
            ], 200);
        } catch (Exception $e) {
            error_log("MAIN ERROR fetching pending verifications: " . $e->getMessage());
            error_log("MAIN ERROR stack trace: " . $e->getTraceAsString());
            $this->log("Error fetching pending verifications: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch pending verifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create test data for verification testing
     * POST /api/admin/verifications/create-test-data
     */
    public function createTestData(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();
            error_log("=== createTestData START ===");

            // Create a test renter with Pending status
            error_log("Creating test user...");
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password_hash, role, created_at) 
                VALUES ('test.renter@example.com', 'password123', 'Renter', NOW())
            ");
            $stmt->execute();
            $userId = $this->pdo->lastInsertId();
            error_log("Created user with ID: " . $userId);

            error_log("Creating test renter...");
            $stmt = $this->pdo->prepare("
                INSERT INTO renters (
                    user_id, first_name, last_name, dob, phone_number, home_address, 
                    gender, nic_number, nic_front_image, nic_back_image, 
                    verification_status, created_at
                ) VALUES (
                    ?, 'Test', 'Renter', '1990-01-01', '0771234567', 'Test Address',
                    'Male', '901234567V', 'test_front.jpg', 'test_back.jpg',
                    'Pending', NOW()
                )
            ");
            $stmt->execute([$userId]);
            error_log("Test renter created successfully");

            $response->json([
                'success' => true,
                'message' => 'Test renter created with Pending status',
                'user_id' => $userId
            ], 200);
        } catch (Exception $e) {
            error_log("ERROR creating test data: " . $e->getMessage());
            error_log("ERROR stack trace: " . $e->getTraceAsString());
            $response->json([
                'success' => false,
                'message' => 'Failed to create test data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending customer verifications
     * GET /api/admin/verifications/pending/customers
     */
    public function getPendingCustomerVerifications(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();
            error_log("=== getPendingCustomerVerifications START ===");

            $stmt = $this->pdo->prepare("
                SELECT 
                    u.user_id,
                    u.role,
                    u.email,
                    c.first_name,
                    c.last_name,
                    c.nic_number,
                    c.nic_front_image,
                    c.nic_back_image,
                    c.verification_status,
                    c.updated_at as verification_requested_at,
                    u.created_at
                FROM users u
                INNER JOIN customers c ON u.user_id = c.user_id
                WHERE u.role = 'Customer' AND c.verification_status = 'Pending'
                ORDER BY c.updated_at DESC
            ");

            $stmt->execute();
            $pendingCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($pendingCustomers) . " pending customers in separate endpoint");
            error_log("Customer data from separate endpoint: " . json_encode($pendingCustomers));

            $response->json([
                'success' => true,
                'data' => $pendingCustomers
            ], 200);
        } catch (Exception $e) {
            error_log("ERROR fetching pending customer verifications: " . $e->getMessage());
            error_log("ERROR stack trace: " . $e->getTraceAsString());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch pending customer verifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending renter verifications
     * GET /api/admin/verifications/pending/renters
     */
    public function getPendingRenterVerifications(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();
            error_log("=== getPendingRenterVerifications START ===");

            $stmt = $this->pdo->prepare("
                SELECT 
                    u.user_id,
                    u.role,
                    u.email,
                    r.first_name,
                    r.last_name,
                    r.nic_number,
                    r.nic_front_image,
                    r.nic_back_image,
                    r.verification_status,
                    r.created_at as verification_requested_at,
                    u.created_at
                FROM users u
                INNER JOIN renters r ON u.user_id = r.user_id
                WHERE u.role = 'Renter' AND r.verification_status = 'Pending'
                ORDER BY r.created_at DESC
            ");

            $stmt->execute();
            $pendingRenters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($pendingRenters) . " pending renters in separate endpoint");
            error_log("Renter data from separate endpoint: " . json_encode($pendingRenters));

            $response->json([
                'success' => true,
                'data' => $pendingRenters
            ], 200);
        } catch (Exception $e) {
            error_log("ERROR fetching pending renter verifications: " . $e->getMessage());
            error_log("ERROR stack trace: " . $e->getTraceAsString());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch pending renter verifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending guide verifications
     * GET /api/admin/verifications/pending/guides
     */
    public function getPendingGuideVerifications(Request $request, Response $response): void
    {
        try {
            $this->requireAdminAuth();
            error_log("=== getPendingGuideVerifications START ===");

            $stmt = $this->pdo->prepare("
                SELECT 
                    u.user_id,
                    u.role,
                    u.email,
                    g.first_name,
                    g.last_name,
                    g.nic_number,
                    g.nic_front_image,
                    g.nic_back_image,
                    g.verification_status,
                    g.created_at as verification_requested_at,
                    u.created_at
                FROM users u
                INNER JOIN guides g ON u.user_id = g.user_id
                WHERE u.role = 'Guide' AND g.verification_status = 'Pending'
                ORDER BY g.created_at DESC
            ");

            $stmt->execute();
            $pendingGuides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($pendingGuides) . " pending guides in separate endpoint");
            error_log("Guide data from separate endpoint: " . json_encode($pendingGuides));

            $response->json([
                'success' => true,
                'data' => $pendingGuides
            ], 200);
        } catch (Exception $e) {
            error_log("ERROR fetching pending guide verifications: " . $e->getMessage());
            error_log("ERROR stack trace: " . $e->getTraceAsString());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch pending guide verifications',
                'error' => $e->getMessage()
            ], 500);
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
            error_log("=== getRejectedUsers START ===");

            // Get rejected users from user_verifications table
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
                    END as nic_back_image
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

            error_log("Found " . count($rejectedUsers) . " rejected users");
            error_log("Rejected users data: " . json_encode($rejectedUsers));

            $response->json([
                'success' => true,
                'data' => $rejectedUsers
            ], 200);
        } catch (Exception $e) {
            error_log("ERROR fetching rejected users: " . $e->getMessage());
            error_log("ERROR stack trace: " . $e->getTraceAsString());
            $this->log("Error fetching rejected users: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch rejected users',
                'error' => $e->getMessage()
            ], 500);
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
            error_log("=== getVerificationActivityLog START ===");

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
                LIMIT 50
            ");
            $stmt->execute();
            $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($activityLog) . " activity log entries");
            error_log("Activity log data: " . json_encode($activityLog));

            $response->json([
                'success' => true,
                'data' => $activityLog
            ], 200);
        } catch (Exception $e) {
            error_log("ERROR fetching verification activity log: " . $e->getMessage());
            error_log("ERROR stack trace: " . $e->getTraceAsString());
            $this->log("Error fetching verification activity log: " . $e->getMessage(), 'ERROR');
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch verification activity log',
                'error' => $e->getMessage()
            ], 500);
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
