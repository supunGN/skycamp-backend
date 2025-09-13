<?php

/**
 * Database Connection Factory
 * Creates PDO connections using environment configuration
 */

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Initialize database configuration
     */
    public static function init(): void
    {
        // Load environment configuration
        $envFile = __DIR__ . '/env.php';
        if (file_exists($envFile)) {
            self::$config = require $envFile;
        } else {
            // Fallback to example config for development
            self::$config = require __DIR__ . '/env.example.php';
        }
    }

    /**
     * Get database connection instance
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::init();
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Create new PDO connection
     */
    private static function createConnection(): PDO
    {
        $dbConfig = self::$config['database'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['dbname'],
            $dbConfig['charset']
        );

        try {
            $pdo = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );

            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get configuration value
     */
    public static function getConfig(string $key = null): mixed
    {
        if (self::$config === []) {
            self::init();
        }

        if ($key === null) {
            return self::$config;
        }

        return self::$config[$key] ?? null;
    }

    /**
     * Test database connection
     */
    public static function testConnection(): bool
    {
        try {
            $pdo = self::getConnection();
            $pdo->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Create global PDO instance for backward compatibility
$pdo = Database::getConnection();
