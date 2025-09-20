<?php

/**
 * Base Controller Class
 * Provides common functionality for all controllers
 */

abstract class Controller
{
    protected Request $request;
    protected Response $response;
    protected Session $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
    }

    /**
     * Get current authenticated user
     */
    protected function getCurrentUser(): ?array
    {
        return $this->session->getUser();
    }

    /**
     * Get current authenticated admin
     */
    protected function getCurrentAdmin(): ?array
    {
        return $this->session->getAdmin();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return $this->session->isAuthenticated();
    }

    /**
     * Check if admin is authenticated
     */
    protected function isAdminAuthenticated(): bool
    {
        return $this->session->isAdminAuthenticated();
    }

    /**
     * Check if any user (user or admin) is authenticated
     */
    protected function isAnyAuthenticated(): bool
    {
        return $this->session->isAnyAuthenticated();
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->response->unauthorized('Authentication required');
        }
    }

    /**
     * Require admin authentication
     */
    protected function requireAdminAuth(): void
    {
        if (!$this->isAdminAuthenticated()) {
            $this->response->unauthorized('Admin authentication required');
        }
    }

    /**
     * Get validated input data
     */
    protected function validate(array $rules): array
    {
        $validator = new Validator();
        return $validator->validate($this->request->all(), $rules);
    }

    /**
     * Handle validation errors
     */
    protected function handleValidationErrors(array $errors): void
    {
        if (!empty($errors)) {
            $this->response->validationError($errors);
        }
    }

    /**
     * Log message
     */
    protected function log(string $message, string $level = 'INFO'): void
    {
        $logFile = __DIR__ . '/../../logs/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;

        // Ensure logs directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
