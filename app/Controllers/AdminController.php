<?php

class AdminController extends Controller
{
    private Session $session;
    private PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->session = new Session();
        $this->pdo = Database::getConnection();
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

        // Set admin session
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_id'] = $row['admin_id'];
        $_SESSION['admin_email'] = $row['email'];
        $this->session->regenerate();

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
        if (!($_SESSION['admin_authenticated'] ?? false)) {
            $response->json(['success' => false, 'authenticated' => false], 401);
            return;
        }

        $response->json([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'admin_id' => $_SESSION['admin_id'] ?? null,
                'email' => $_SESSION['admin_email'] ?? null,
            ]
        ], 200);
    }

    public function logout(Request $request, Response $response): void
    {
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_id'], $_SESSION['admin_email']);
        $this->session->regenerate();
        $response->json(['success' => true], 200);
    }
}
