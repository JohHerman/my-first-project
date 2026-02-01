<?php
// ============================================
// AUTH API ENDPOINT
// Streaming Platform Backend System
// ============================================

// Load configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Set headers
header('Content-Type: application/json');
set_security_headers();
set_cors_headers();

// Load controller
require_once __DIR__ . '/../app/controllers/AuthController.php';

// Create controller instance
$controller = new AuthController();

// Route the request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// FIX: Handle GET requests to login page (show instructions)
if ($method === 'GET' && $action === 'login') {
    echo json_encode([
        'message' => 'Login endpoint',
        'instructions' => 'Send POST request with JSON body: {"email": "your@email.com", "password": "yourpassword"}',
        'test_credentials' => [
            'admin' => 'admin@stream.com / password123',
            'user' => 'user@stream.com / password123'
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    switch ($method) {
        case 'POST':
            if ($action === 'register') {
                $controller->register();
            } elseif ($action === 'login') {
                $controller->login();
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Action not found', 
                    'available_actions' => ['login', 'register'],
                    'usage' => 'Send POST request with JSON body containing email and password'
                ]);
            }
            break;
            
        case 'GET':
            if ($action === 'profile') {
                $controller->profile();
            } elseif ($action === 'validate') {
                $controller->validate();
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Action not found', 
                    'available_actions' => ['profile', 'validate'],
                    'note' => 'For login, use POST method'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>