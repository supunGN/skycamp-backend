<?php

/**
 * Authentication Controller
 * Handles authentication-related API endpoints
 */

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * Register a new user
     * POST /api/auth/register
     */
    public function register(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->register($request);

            if ($result['success']) {
                $response->json([
                    'success' => true,
                    'user' => $result['user'],
                    'redirect_url' => $result['data']['redirect_url'] ?? '/'
                ], 201);
            } else {
                if (isset($result['errors'])) {
                    $response->validationError($result['errors']);
                } else {
                    $statusCode = strpos($result['message'], 'already') !== false ? 409 : 400;
                    $response->error($result['message'], $statusCode);
                }
            }
        } catch (Exception $e) {
            $this->log("Registration error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Registration failed');
        }
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request, Response $response): void
    {
        try {
            $email = $request->json('email');
            $password = $request->json('password');

            if (empty($email) || empty($password)) {
                $response->error('Email and password are required', 400);
                return;
            }

            $result = $this->authService->login($email, $password);

            if ($result['success']) {
                $response->json([
                    'success' => true,
                    'user' => $result['user'],
                    'redirect_url' => $result['data']['redirect_url'] ?? '/'
                ], 200);
            } else {
                $response->error($result['message'], 401);
            }
        } catch (Exception $e) {
            $this->log("Login error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Login failed');
        }
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): void
    {
        try {
            $result = $this->authService->logout();

            if ($result['success']) {
                $response->success(null, 'Logout successful');
            } else {
                $response->error($result['message'], 500);
            }
        } catch (Exception $e) {
            $this->log("Logout error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Logout failed');
        }
    }

    /**
     * Get current user
     * GET /api/auth/me
     */
    public function me(Request $request, Response $response): void
    {
        try {
            $user = $this->authService->getCurrentUser();

            if ($user) {
                $response->json([
                    'success' => true,
                    'data' => [
                        'authenticated' => true,
                        'user' => $user
                    ]
                ], 200);
            } else {
                $response->json([
                    'success' => false,
                    'data' => [
                        'authenticated' => false,
                        'message' => 'Not authenticated'
                    ]
                ], 401);
            }
        } catch (Exception $e) {
            $this->log("Get user error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to get user');
        }
    }
}
