<?php

/**
 * Session Management Utility Class
 * Handles user sessions securely following best practices
 * Implements session security from Lecture 5
 */

class SessionManager
{

    private $session_lifetime = 3600; // 1 hour
    private $session_name = 'SKYCAMP_SESSION';

    /**
     * Constructor - Initialize secure session
     */
    public function __construct()
    {
        $this->configureSession();
    }

    /**
     * Configure session settings for security
     */
    private function configureSession()
    {
        // Set session name
        session_name($this->session_name);

        // Configure session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', $this->session_lifetime);

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $this->session_lifetime,
            'path' => '/',
            'domain' => '', // Set your domain
            'secure' => false, // Set to true for HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Start session safely
     * 
     * @return bool Success status
     */
    public function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (session_start()) {
                $this->regenerateSessionId();
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Create user session after successful authentication
     * 
     * @param array $user_data User data
     * @return array Success status and session ID
     */
    public function createUserSession($user_data)
    {
        if (!$this->startSession()) {
            return [
                'success' => false,
                'message' => 'Failed to start session',
                'session_id' => null
            ];
        }

        // Store user data in session
        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['user_role'] = $user_data['user_role'] ?? $user_data['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $this->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Store all user data in session
        foreach ($user_data as $key => $value) {
            $_SESSION[$key] = $value;
        }

        return [
            'success' => true,
            'message' => 'Session created successfully',
            'session_id' => session_id()
        ];
    }

    /**
     * Check if user is logged in and session is valid
     * 
     * @return bool True if valid session, false otherwise
     */
    public function isValidSession()
    {
        $this->startSession();

        // Check basic session data
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->destroySession();
            return false;
        }

        // Check IP address (optional security measure)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $this->getClientIP()) {
            $this->destroySession();
            return false;
        }

        // Update last activity
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Check if session has expired
     * 
     * @return bool True if expired, false otherwise
     */
    private function isSessionExpired()
    {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }

        return (time() - $_SESSION['last_activity']) > $this->session_lifetime;
    }

    /**
     * Get current user data from session
     * 
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser()
    {
        if (!$this->isValidSession()) {
            return null;
        }

        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'customer_id' => $_SESSION['customer_id'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null
        ];
    }

    /**
     * Update session data
     * 
     * @param array $data Data to update
     */
    public function updateSessionData($data)
    {
        if ($this->isValidSession()) {
            foreach ($data as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }
    }

    /**
     * Destroy user session
     * 
     * @return array Success status
     */
    public function destroySession()
    {
        try {
            $this->startSession();

            // Clear session data
            $_SESSION = array();

            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            // Destroy session
            session_destroy();

            return [
                'success' => true,
                'message' => 'Session destroyed successfully'
            ];
        } catch (Exception $e) {
            error_log("Session destruction error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to destroy session'
            ];
        }
    }

    /**
     * Regenerate session ID for security
     */
    private function regenerateSessionId()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function getClientIP()
    {
        // Check for various headers that might contain the real IP
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Check if user has specific role
     * 
     * @param string $required_role Required role
     * @return bool True if user has role, false otherwise
     */
    public function hasRole($required_role)
    {
        if (!$this->isValidSession()) {
            return false;
        }

        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $required_role;
    }

    /**
     * Extend session lifetime
     * 
     * @param int $additional_time Additional time in seconds
     */
    public function extendSession($additional_time = 1800)
    {
        if ($this->isValidSession()) {
            $_SESSION['last_activity'] = time() + $additional_time;
        }
    }

    /**
     * Get session statistics
     * 
     * @return array|null Session statistics or null if session is invalid
     */
    public function getSessionStats()
    {
        if (!$this->isValidSession()) {
            return null;
        }

        $current_time = time();
        $login_duration = $current_time - ($_SESSION['login_time'] ?? $current_time);
        $time_remaining = $this->session_lifetime - ($current_time - ($_SESSION['last_activity'] ?? $current_time));

        return [
            'session_id' => session_id(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'login_duration' => $login_duration,
            'time_remaining' => max(0, $time_remaining),
            'ip_address' => $_SESSION['ip_address'] ?? null
        ];
    }
}
