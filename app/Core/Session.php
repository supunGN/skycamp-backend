<?php

/**
 * Enhanced Session Management Class
 * Handles secure user and admin sessions with idle timeout and regeneration
 */

class Session
{
    private bool $started = false;
    private array $config;
    private ?int $lastRegeneration = null;

    public function __construct()
    {
        $this->config = Database::getConfig('session') ?? [];
        $this->start();
        $this->checkIdleTimeout();
        $this->checkRegenerationInterval();
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
        $this->set('session_type', 'user');
        $this->updateLastActivity();
        $this->regenerate();
    }

    /**
     * Set admin authentication data
     */
    public function setAdmin(array $admin): void
    {
        $this->set('admin_id', $admin['admin_id']);
        $this->set('admin_email', $admin['email']);
        $this->set('admin_authenticated', true);
        $this->set('session_type', 'admin');
        $this->updateLastActivity();
        $this->regenerate();
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
     * Check if admin is authenticated
     */
    public function isAdminAuthenticated(): bool
    {
        return $this->get('admin_authenticated', false) === true;
    }

    /**
     * Check if any user (user or admin) is authenticated
     */
    public function isAnyAuthenticated(): bool
    {
        return $this->isAuthenticated() || $this->isAdminAuthenticated();
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
     * Regenerate session ID and update timestamp
     */
    public function regenerate(): void
    {
        if ($this->started) {
            session_regenerate_id(true);
            $this->set('last_regeneration', time());
            $this->lastRegeneration = time();
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

    /**
     * Check idle timeout and destroy session if expired
     */
    private function checkIdleTimeout(): void
    {
        $idleTimeout = $this->config['idle_timeout'] ?? 1800; // 30 minutes default
        $lastActivity = $this->get($this->config['last_activity_key'] ?? 'last_activity');

        if ($lastActivity && (time() - $lastActivity) > $idleTimeout) {
            $this->destroy();
            return;
        }

        // Update last activity on each request
        $this->updateLastActivity();
    }

    /**
     * Check if session ID needs regeneration
     */
    private function checkRegenerationInterval(): void
    {
        $regenerationInterval = $this->config['regeneration_interval'] ?? 300; // 5 minutes default
        $lastRegeneration = $this->get('last_regeneration');

        if (!$lastRegeneration || (time() - $lastRegeneration) > $regenerationInterval) {
            $this->regenerate();
        }
    }

    /**
     * Update last activity timestamp
     */
    private function updateLastActivity(): void
    {
        $this->set($this->config['last_activity_key'] ?? 'last_activity', time());
    }

    /**
     * Get admin data
     */
    public function getAdmin(): ?array
    {
        if (!$this->isAdminAuthenticated()) {
            return null;
        }

        return [
            'admin_id' => $this->get('admin_id'),
            'email' => $this->get('admin_email')
        ];
    }

    /**
     * Get current session type
     */
    public function getSessionType(): ?string
    {
        return $this->get($this->config['session_type_key'] ?? 'session_type');
    }

    /**
     * Check if session is for admin
     */
    public function isAdminSession(): bool
    {
        return $this->getSessionType() === 'admin';
    }

    /**
     * Check if session is for user
     */
    public function isUserSession(): bool
    {
        return $this->getSessionType() === 'user';
    }

    /**
     * Unified logout for both user and admin sessions
     */
    public function logout(): void
    {
        $this->destroy();
    }

    /**
     * Get session security info
     */
    public function getSecurityInfo(): array
    {
        return [
            'session_id' => $this->getId(),
            'last_activity' => $this->get($this->config['last_activity_key'] ?? 'last_activity'),
            'last_regeneration' => $this->get('last_regeneration'),
            'session_type' => $this->getSessionType(),
            'is_authenticated' => $this->isAnyAuthenticated(),
            'idle_timeout' => $this->config['idle_timeout'] ?? 1800,
            'regeneration_interval' => $this->config['regeneration_interval'] ?? 300
        ];
    }
}
