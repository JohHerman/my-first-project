<?php
// ============================================
// RESPONSE HANDLER
// Streaming Platform Backend System
// ============================================

class Response {
    
    // Send JSON response
    public static function json($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Send success response
    public static function success($data = [], $message = 'Success', $status_code = 200) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status_code);
    }
    
    // Send error response
    public static function error($message = 'Error', $status_code = 400, $errors = []) {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status_code);
    }
    
    // Send validation error
    public static function validationError($errors = []) {
        self::error('Validation failed', 422, $errors);
    }
    
    // Send not found
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    // Send unauthorized
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    // Send forbidden
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
}
?>