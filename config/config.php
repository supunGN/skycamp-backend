<?php

/**
 * Configuration Management Class
 * Centralized configuration for the SkyCamp application
 * Following best practices for environment management
 */

class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Load configuration from environment and defaults
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        // Default configuration
        self::$config = [
            // Database Configuration
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'skycamp',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            ],

            // Application Configuration
            'app' => [
                'name' => 'SkyCamp',
                'version' => '1.0.0',
                'timezone' => 'Asia/Colombo',
                'debug' => $_ENV['APP_DEBUG'] ?? true,
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'api_prefix' => '/api'
            ],

            // Security Configuration
            'security' => [
                'session_lifetime' => 3600, // 1 hour
                'session_name' => 'SKYCAMP_SESSION',
                'password_min_length' => 8,
                'max_login_attempts' => 5,
                'lockout_duration' => 900, // 15 minutes
                'csrf_token_name' => 'csrf_token',
                'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? 'your-secret-key-here'
            ],

            // File Upload Configuration
            'uploads' => [
                'max_file_size' => 5 * 1024 * 1024, // 5MB
                'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'upload_path' => __DIR__ . '/../uploads/',
                'profile_pictures_path' => 'profile_pictures/',
                'nic_images_path' => 'nic_images/'
            ],

            // Email Configuration (for future use)
            'email' => [
                'smtp_host' => $_ENV['SMTP_HOST'] ?? '',
                'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
                'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
                'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
                'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@skycamp.lk',
                'from_name' => 'SkyCamp'
            ],

            // API Configuration
            'api' => [
                'rate_limit' => 100, // requests per minute
                'cors_origins' => ['http://localhost:3000', 'http://localhost:5173'],
                'cors_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'cors_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
            ],

            // Logging Configuration
            'logging' => [
                'log_level' => $_ENV['LOG_LEVEL'] ?? 'error',
                'log_file' => __DIR__ . '/../logs/app.log',
                'max_log_size' => 10 * 1024 * 1024, // 10MB
                'log_rotation' => true
            ],

            // Cache Configuration (for future use)
            'cache' => [
                'driver' => 'file',
                'ttl' => 3600, // 1 hour
                'path' => __DIR__ . '/../cache/'
            ],

            // Validation Rules
            'validation' => [
                'sri_lanka_districts' => [
                    'Colombo',
                    'Gampaha',
                    'Kalutara',
                    'Kandy',
                    'Matale',
                    'Nuwara Eliya',
                    'Galle',
                    'Matara',
                    'Hambantota',
                    'Jaffna',
                    'Kilinochchi',
                    'Mannar',
                    'Vavuniya',
                    'Mullaitivu',
                    'Batticaloa',
                    'Ampara',
                    'Trincomalee',
                    'Kurunegala',
                    'Puttalam',
                    'Anuradhapura',
                    'Polonnaruwa',
                    'Badulla',
                    'Moneragala',
                    'Ratnapura',
                    'Kegalle'
                ],
                'supported_languages' => ['Sinhala', 'Tamil', 'English'],
                'gender_options' => ['Male', 'Female', 'Other'],
                'user_roles' => ['Customer', 'Renter', 'Guide', 'Admin']
            ]
        ];

        // Load environment-specific overrides
        self::loadEnvironmentConfig();

        self::$loaded = true;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key using dot notation
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     * 
     * @param string $key Configuration key using dot notation
     * @param mixed $value Configuration value
     */
    public static function set($key, $value)
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Get all configuration
     * 
     * @return array All configuration
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists, false otherwise
     */
    public static function has($key)
    {
        return self::get($key) !== null;
    }

    /**
     * Load environment-specific configuration
     */
    private static function loadEnvironmentConfig()
    {
        $env = $_ENV['APP_ENV'] ?? 'development';
        $envConfigFile = __DIR__ . "/config.{$env}.php";

        if (file_exists($envConfigFile)) {
            $envConfig = require $envConfigFile;
            self::$config = array_merge_recursive(self::$config, $envConfig);
        }
    }

    /**
     * Get database configuration for PDO
     * 
     * @return array Database configuration
     */
    public static function getDatabaseConfig()
    {
        return self::get('database');
    }

    /**
     * Get upload configuration
     * 
     * @return array Upload configuration
     */
    public static function getUploadConfig()
    {
        return self::get('uploads');
    }

    /**
     * Get security configuration
     * 
     * @return array Security configuration
     */
    public static function getSecurityConfig()
    {
        return self::get('security');
    }

    /**
     * Check if application is in debug mode
     * 
     * @return bool True if debug mode, false otherwise
     */
    public static function isDebug()
    {
        return self::get('app.debug', false);
    }

    /**
     * Get application environment
     * 
     * @return string Application environment
     */
    public static function getEnvironment()
    {
        return $_ENV['APP_ENV'] ?? 'development';
    }

    /**
     * Check if application is in production
     * 
     * @return bool True if production, false otherwise
     */
    public static function isProduction()
    {
        return self::getEnvironment() === 'production';
    }
}
