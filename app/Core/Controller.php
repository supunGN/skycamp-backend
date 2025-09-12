<?php

/**
 * Base Controller Class
 * Provides common functionality for all controllers
 */

abstract class Controller
{
    protected Request $request;
    protected Response $response;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Get current authenticated user
     */
    protected function getCurrentUser(): ?array
    {
        $session = new Session();
        return $session->getUser();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        $session = new Session();
        return $session->isAuthenticated();
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
