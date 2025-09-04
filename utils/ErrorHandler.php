<?php

/**
 * Error Handler Class
 * Centralized error handling and logging system
 * Following error handling principles from Lecture 4 - Database handling
 */

class ErrorHandler
{
    private static $logFile = __DIR__ . '/../logs/error.log';

    /**
     * Initialize error handler
     */
    public static function init()
    {
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);

        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);

        // Register shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleFatalError']);

        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Handle PHP errors
     */
    public static function handleError($severity, $message, $file, $line)
    {
        // Don't handle suppressed errors
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorData = [
            'type' => 'PHP Error',
            'severity' => self::getSeverityName($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        self::logError($errorData);

        // For development, show errors
        if (self::isDevelopment()) {
            self::displayError($errorData);
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception)
    {
        $errorData = [
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        self::logError($errorData);

        // Send appropriate response
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');

            if (self::isDevelopment()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'debug' => $errorData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => 'An unexpected error occurred'
                ]);
            }
        }
    }

    /**
     * Handle fatal errors
     */
    public static function handleFatalError()
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI'
            ];

            self::logError($errorData);

            // Send error response for API calls
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Fatal Error',
                    'message' => 'The server encountered a fatal error'
                ]);
            }
        }
    }

    /**
     * Log database errors
     */
    public static function logDatabaseError($message, $query = '', $params = [])
    {
        $errorData = [
            'type' => 'Database Error',
            'message' => $message,
            'query' => $query,
            'parameters' => $params,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        self::logError($errorData);
    }

    /**
     * Log validation errors
     */
    public static function logValidationError($field, $message, $data = [])
    {
        $errorData = [
            'type' => 'Validation Error',
            'field' => $field,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI'
        ];

        self::logError($errorData);
    }

    /**
     * Log authentication errors
     */
    public static function logAuthError($message, $email = '', $ip = '')
    {
        $errorData = [
            'type' => 'Authentication Error',
            'message' => $message,
            'email' => $email,
            'ip_address' => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI'
        ];

        self::logError($errorData);
    }

    /**
     * Log application errors
     */
    public static function logAppError($message, $context = [])
    {
        $errorData = [
            'type' => 'Application Error',
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];

        self::logError($errorData);
    }

    /**
     * General logging method for exceptions and errors
     */
    public static function log($exception)
    {
        if ($exception instanceof Exception) {
            $errorData = [
                'type' => 'Exception',
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI'
            ];
        } else {
            $errorData = [
                'type' => 'General Error',
                'message' => is_string($exception) ? $exception : print_r($exception, true),
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ];
        }

        self::logError($errorData);
    }

    /**
     * Write error to log file
     */
    private static function logError($errorData)
    {
        $logEntry = json_encode($errorData, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n";

        // Append to log file
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Rotate log file if it gets too large (10MB)
        if (file_exists(self::$logFile) && filesize(self::$logFile) > 10 * 1024 * 1024) {
            self::rotateLogFile();
        }
    }

    /**
     * Rotate log file when it gets too large
     */
    private static function rotateLogFile()
    {
        $backupFile = self::$logFile . '.' . date('Y-m-d-H-i-s') . '.backup';
        rename(self::$logFile, $backupFile);

        // Keep only last 5 backup files
        $logDir = dirname(self::$logFile);
        $backupFiles = glob($logDir . '/*.backup');

        if (count($backupFiles) > 5) {
            usort($backupFiles, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Remove oldest backup files
            $filesToRemove = array_slice($backupFiles, 0, -5);
            foreach ($filesToRemove as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Display error for development environment
     */
    private static function displayError($errorData)
    {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h4>Error Details:</h4>";
        echo "<pre>" . print_r($errorData, true) . "</pre>";
        echo "</div>";
    }

    /**
     * Get severity name from error level
     */
    private static function getSeverityName($severity)
    {
        $severities = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];

        return $severities[$severity] ?? 'UNKNOWN';
    }

    /**
     * Check if we're in development environment
     */
    private static function isDevelopment()
    {
        return ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' ||
            ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' ||
            strpos(($_SERVER['HTTP_HOST'] ?? ''), '127.0.0.1') !== false;
    }

    /**
     * Create standardized error response
     */
    public static function createErrorResponse($message, $code = 400, $details = [])
    {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'details' => $details
            ],
            'timestamp' => date('c')
        ];
    }

    /**
     * Create standardized success response
     */
    public static function createSuccessResponse($data = null, $message = 'Success')
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }
}
