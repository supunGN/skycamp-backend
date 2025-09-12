<?php

/**
 * Environment Configuration Example
 * Copy this file to env.php and update with your actual values
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'skycamp',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // CORS Configuration
    'cors' => [
        'origin' => 'http://localhost:5173', // Frontend URL
        'methods' => ['GET', 'POST', 'OPTIONS'],
        'headers' => ['Content-Type', 'Authorization'],
        'credentials' => true
    ],

    // Session Configuration
    'session' => [
        'name' => 'SKYCAMP_SESSION',
        'lifetime' => 3600, // 1 hour
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ],

    // File Upload Configuration
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'storage_path' => __DIR__ . '/../../storage/uploads'
    ],

    // Application Configuration
    'app' => [
        'name' => 'SkyCamp',
        'version' => '1.0.0',
        'debug' => true, // Set to false in production
        'timezone' => 'UTC'
    ]
];
