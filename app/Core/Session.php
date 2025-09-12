<?php

/**
 * Session Management Class
 * Handles user sessions with cookie-based storage
 */

class Session
{
    private bool $started = false;
    private array $config;

    public function __construct()
    {
        $this->config = Database::getConfig('session') ?? [];
        $this->start();
    }

    /**
     * Start session if not already started
     */
    public function start(): void
    {
        if (!$this->started && session_status() === PHP_SESSION_NONE) {
            // Configure session settings
            if (isset($this->config['name'])) {
                session_name($this->config['name']);
            }

            if (isset($this->config['lifetime'])) {
                ini_set('session.cookie_lifetime', $this->config['lifetime']);
            }

            if (isset($this->config['path'])) {
                ini_set('session.cookie_path', $this->config['path']);
            }

            if (isset($this->config['domain'])) {
                ini_set('session.cookie_domain', $this->config['domain']);
            }

            if (isset($this->config['secure'])) {
                ini_set('session.cookie_secure', $this->config['secure']);
            }

            if (isset($this->config['httponly'])) {
                ini_set('session.cookie_httponly', $this->config['httponly']);
            }

            if (isset($this->config['samesite'])) {
                ini_set('session.cookie_samesite', $this->config['samesite']);
            }

            session_start();
            $this->started = true;
        }
    }

    /**
     * Set session data
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session data
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Set user authentication data
     */
    public function setUser(array $user): void
    {
        $this->set('user_id', $user['user_id']);
        $this->set('user_role', $user['role']);
        $this->set('user_email', $user['email'] ?? null);
        $this->set('authenticated', true);
    }

    /**
     * Get current user data
     */
    public function getUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'user_id' => $this->get('user_id'),
            'role' => $this->get('user_role'),
            'email' => $this->get('user_email')
        ];
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->get('authenticated', false) === true;
    }

    /**
     * Get user role
     */
    public function getUserRole(): ?string
    {
        return $this->get('user_role');
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Destroy session
     */
    public function destroy(): void
    {
        if ($this->started) {
            session_destroy();
            $this->started = false;
        }
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(): void
    {
        if ($this->started) {
            session_regenerate_id(true);
        }
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        return session_id();
    }
}
