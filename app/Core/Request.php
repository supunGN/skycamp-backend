<?php

/**
 * Request Class
 * Wraps $_GET, $_POST, $_FILES, headers, and JSON data
 */

class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $headers;
    private ?array $json;
    private string $method;
    private string $path;
    private string $body;
    private array $params; // URL parameters

    public function __construct()
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->files = $_FILES ?? [];
        $this->headers = $this->getHeaders();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->getRequestPath();
        $this->body = $this->getRequestBody();
        $this->json = $this->parseJsonBody();
        $this->params = []; // Initialize URL parameters
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get GET parameter
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check URL parameters first, then GET parameters
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * Set URL parameter
     */
    public function setParam(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * Get all query parameters (GET parameters)
     */
    public function getQueryParams(): array
    {
        return array_merge($this->params, $this->get);
    }

    /**
     * Get POST parameter
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get uploaded file with safe validation
     */
    public function file(string $key): ?array
    {
        if (!isset($_FILES[$key])) {
            return null;
        }

        $file = $_FILES[$key];

        // Check if file was uploaded successfully
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        // Verify it's actually an uploaded file
        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        return $file;
    }

    /**
     * Get request header
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get JSON data
     */
    public function json(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->json;
        }

        return $this->json[$key] ?? $default;
    }

    /**
     * Get raw request body
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return $this->header('content-type') === 'application/json';
    }

    /**
     * Get all input data (GET + POST + JSON)
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json ?? []);
    }

    /**
     * Check if request is multipart form data
     */
    public function isMultipart(): bool
    {
        $contentType = $this->header('content-type');
        return strpos($contentType, 'multipart/form-data') !== false;
    }

    /**
     * Get sanitized form data
     */
    public function getFormData(): array
    {
        $data = $this->all();

        // Sanitize string inputs
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    /**
     * Get request headers
     */
    private function getHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($headerName)] = $value;
            }
        }

        // Handle Content-Type and Authorization specially
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        return $headers;
    }

    /**
     * Get request path from URI
     */
    private function getRequestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        // Remove the base path if it's part of the URI
        // For XAMPP: /skycamp/skycamp-backend/public/api/auth/me -> /api/auth/me
        if (str_contains($path, '/skycamp/skycamp-backend/public/')) {
            $path = str_replace('/skycamp/skycamp-backend/public', '', $path);
        }

        return $path ?: '/';
    }

    /**
     * Get raw request body
     */
    private function getRequestBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Parse JSON request body
     */
    private function parseJsonBody(): ?array
    {
        if (!$this->isJson() || empty($this->body)) {
            return null;
        }

        $data = json_decode($this->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }
}
