<?php
// ============================================
// AUTH CONTROLLER
// Streaming Platform Backend System
// ============================================

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';

class AuthController {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    // Handle user registration
    public function register() {
        try {
            // Get request data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception("Invalid request data");
            }
            
            // Validate required fields
            $required = ['email', 'password', 'username'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("$field is required");
                }
            }
            
            // Sanitize inputs
            $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
            $password = trim($data['password']);
            $username = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($data['username']));
            $full_name = isset($data['full_name']) ? trim($data['full_name']) : '';
            
            // Register user
            $result = $this->auth->register($email, $password, $username, $full_name);
            
            $this->sendJsonResponse(201, $result);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 400);
        }
    }
    
    // Handle user login
    public function login() {
        try {
            // Get request data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception("Invalid request data");
            }
            
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                throw new Exception("Email and password are required");
            }
            
            // Sanitize inputs
            $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
            $password = trim($data['password']);
            
            // Login user
            $result = $this->auth->login($email, $password);
            
            $this->sendJsonResponse(200, $result);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 401);
        }
    }
    
    // Get user profile
    public function profile() {
        try {
            // Get authorization header
            $headers = getallheaders();
            $auth_header = $headers['Authorization'] ?? '';
            
            if (empty($auth_header) || !preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
                throw new Exception("Authorization token required");
            }
            
            $token = $matches[1];
            
            // Get current user
            $user = $this->auth->getCurrentUser($token);
            
            // Get user's subscription
            $db = Database::getInstance();
            $subscription = $db->fetchOne(
                "SELECT plan, expires_at FROM subscriptions WHERE user_id = ?",
                [$user['id']]
            );
            
            // Get user's stats
            $video_count = $db->fetchOne(
                "SELECT COUNT(*) as count FROM videos WHERE user_id = ?",
                [$user['id']]
            );
            
            $watch_history = $db->fetchAll(
                "SELECT COUNT(*) as count FROM watch_history WHERE user_id = ?",
                [$user['id']]
            );
            
            $result = [
                'success' => true,
                'user' => $user,
                'stats' => [
                    'videos_uploaded' => $video_count['count'],
                    'videos_watched' => $watch_history[0]['count']
                ],
                'subscription' => $subscription
            ];
            
            $this->sendJsonResponse(200, $result);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 401);
        }
    }
    
    // Validate token
    public function validate() {
        try {
            $headers = getallheaders();
            $auth_header = $headers['Authorization'] ?? '';
            
            if (empty($auth_header) || !preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
                throw new Exception("Authorization token required");
            }
            
            $token = $matches[1];
            $valid = $this->auth->validateToken($token);
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'valid' => $valid !== false,
                'message' => $valid ? 'Token is valid' : 'Token is invalid or expired'
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 401);
        }
    }
    
    // Helper method to send JSON response
    private function sendJsonResponse($status_code, $data) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    
    // Helper method to send JSON error
    private function sendJsonError($message, $status_code = 400) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_PRETTY_PRINT);
    }
}