<?php
/**
 * Response Utility Functions
 */

class ResponseHelper {
    /**
     * Send JSON response
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success($message, $data = null) {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        self::json($response);
    }
    
    /**
     * Send error response
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        $response = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        self::json($response, $statusCode);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors) {
        self::error('Validation failed', 422, $errors);
    }
}
?>
