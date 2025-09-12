<?php

/**
 * Response Class
 * Handles JSON responses and status codes
 */

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $data = null;

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send JSON response
     */
    public function json(mixed $data, int $statusCode = null): void
    {
        if ($statusCode !== null) {
            $this->setStatusCode($statusCode);
        }

        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->data = $data;
        $this->send();
    }

    /**
     * Static method to send JSON response directly
     */
    public static function jsonResponse(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success response
     */
    public function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Send error response
     */
    public function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Send validation error response
     */
    public function validationError(array $errors): void
    {
        $this->error('Validation failed', 422, $errors);
    }

    /**
     * Send unauthorized response
     */
    public function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, 403);
    }

    /**
     * Send not found response
     */
    public function notFound(string $message = 'Not found'): void
    {
        $this->error($message, 404);
    }

    /**
     * Send server error response
     */
    public function serverError(string $message = 'Internal server error'): void
    {
        $this->error($message, 500);
    }

    /**
     * Send the response
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send data
        if ($this->data !== null) {
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Stop execution
        exit;
    }

    /**
     * Redirect to another URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->send();
    }
}
