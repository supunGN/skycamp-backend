<?php

/**
 * CORS Middleware
 * Handles Cross-Origin Resource Sharing headers and preflight requests
 */

class Cors
{
    private array $config;

    public function __construct()
    {
        $this->config = Database::getConfig('cors') ?? [];
    }

    /**
     * Handle CORS headers and preflight requests
     */
    public function handle(): void
    {
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->handlePreflight();
            return;
        }

        // Set CORS headers for actual requests
        $this->setCorsHeaders();
    }

    /**
     * Handle preflight OPTIONS requests
     */
    private function handlePreflight(): void
    {
        $this->setCorsHeaders();

        // Set additional preflight headers
        $maxAge = 86400; // 24 hours
        header("Access-Control-Max-Age: {$maxAge}");

        // End the request with 204 No Content
        http_response_code(204);
        exit;
    }

    /**
     * Set CORS headers
     */
    private function setCorsHeaders(): void
    {
        // Origin
        $origin = $this->getAllowedOrigin();
        if ($origin) {
            header("Access-Control-Allow-Origin: {$origin}");
        }

        // Methods
        $methods = $this->config['methods'] ?? ['GET', 'POST', 'OPTIONS'];
        header("Access-Control-Allow-Methods: " . implode(', ', $methods));

        // Headers
        $headers = $this->config['headers'] ?? ['Content-Type', 'Authorization'];
        header("Access-Control-Allow-Headers: " . implode(', ', $headers));

        // Credentials
        $credentials = $this->config['credentials'] ?? true;
        if ($credentials) {
            header("Access-Control-Allow-Credentials: true");
        }

        // Expose headers (if needed)
        $exposeHeaders = $this->config['expose_headers'] ?? [];
        if (!empty($exposeHeaders)) {
            header("Access-Control-Expose-Headers: " . implode(', ', $exposeHeaders));
        }
    }

    /**
     * Get allowed origin based on request
     */
    private function getAllowedOrigin(): ?string
    {
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $allowedOrigin = $this->config['origin'] ?? '*';

        // If wildcard is allowed
        if ($allowedOrigin === '*') {
            return '*';
        }

        // If specific origin is configured
        if ($allowedOrigin && $requestOrigin === $allowedOrigin) {
            return $allowedOrigin;
        }

        // If multiple origins are configured (as array)
        if (is_array($this->config['origin'])) {
            $allowedOrigins = $this->config['origin'];
            if (in_array($requestOrigin, $allowedOrigins)) {
                return $requestOrigin;
            }
        }

        // Default: no origin allowed
        return null;
    }

    /**
     * Check if request origin is allowed
     */
    public function isOriginAllowed(string $origin): bool
    {
        $allowedOrigin = $this->config['origin'] ?? '*';

        if ($allowedOrigin === '*') {
            return true;
        }

        if (is_array($allowedOrigin)) {
            return in_array($origin, $allowedOrigin);
        }

        return $origin === $allowedOrigin;
    }

    /**
     * Get CORS configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set CORS configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Add allowed origin
     */
    public function addOrigin(string $origin): void
    {
        if (!isset($this->config['origin'])) {
            $this->config['origin'] = [];
        }

        if (!is_array($this->config['origin'])) {
            $this->config['origin'] = [$this->config['origin']];
        }

        if (!in_array($origin, $this->config['origin'])) {
            $this->config['origin'][] = $origin;
        }
    }

    /**
     * Add allowed method
     */
    public function addMethod(string $method): void
    {
        if (!isset($this->config['methods'])) {
            $this->config['methods'] = [];
        }

        if (!in_array($method, $this->config['methods'])) {
            $this->config['methods'][] = $method;
        }
    }

    /**
     * Add allowed header
     */
    public function addHeader(string $header): void
    {
        if (!isset($this->config['headers'])) {
            $this->config['headers'] = [];
        }

        if (!in_array($header, $this->config['headers'])) {
            $this->config['headers'][] = $header;
        }
    }
}
