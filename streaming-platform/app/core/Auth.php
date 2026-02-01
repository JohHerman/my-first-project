<?php
// ============================================
// AUTHENTICATION CORE CLASS
// Streaming Platform Backend System
// ============================================

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Register new user
    public function register($email, $password, $username, $full_name = '') {
        // Validate inputs
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters");
        }
        
        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters");
        }
        
        // Check if user exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ? OR username = ?",
            [$email, $username]
        );
        
        if ($existing) {
            throw new Exception("Email or username already exists");
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $user_id = $this->db->insert('users', [
            'email' => $email,
            'username' => $username,
            'password_hash' => $password_hash,
            'full_name' => $full_name,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create default subscription
        $this->db->insert('subscriptions', [
            'user_id' => $user_id,
            'plan' => 'free',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ]);
        
        // Generate JWT token
        $token = $this->generateJWT($user_id, $email, 'user');
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $user_id,
            'token' => $token,
            'user' => [
                'id' => $user_id,
                'email' => $email,
                'username' => $username,
                'full_name' => $full_name,
                'role' => 'user'
            ]
        ];
    }
    
    // User login
    public function login($email, $password) {
        // Get user
        $user = $this->db->fetchOne(
            "SELECT id, email, username, password_hash, full_name, role FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            throw new Exception("Invalid email or password");
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid email or password");
        }
        
        // Update last login
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        // Generate token
        $token = $this->generateJWT($user['id'], $user['email'], $user['role']);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
    }
    
    // Validate JWT token
    public function validateToken($token) {
        try {
            // Split token
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            list($header, $payload, $signature) = $parts;
            
            // Verify signature
            $expected_signature = hash_hmac('sha256', 
                "$header.$payload", 
                JWT_SECRET, 
                true
            );
            
            $expected_signature_base64 = base64_encode($expected_signature);
            
            if (!hash_equals($signature, $expected_signature_base64)) {
                return false;
            }
            
            // Decode payload
            $payload_data = json_decode(base64_decode($payload), true);
            
            // Check expiration
            if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
                return false;
            }
            
            // Verify user still exists
            $user = $this->db->fetchOne(
                "SELECT id, email, role FROM users WHERE id = ? AND email = ?",
                [$payload_data['user_id'], $payload_data['email']]
            );
            
            return $user ? $payload_data : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get current user from token
    public function getCurrentUser($token) {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            throw new Exception("Invalid or expired token");
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, email, username, full_name, role FROM users WHERE id = ?",
            [$payload['user_id']]
        );
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        return $user;
    }
    
    // Generate JWT token
    private function generateJWT($user_id, $email, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user_id,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY
        ]);
        
        $base64_header = base64_encode($header);
        $base64_payload = base64_encode($payload);
        
        $signature = hash_hmac('sha256', 
            "$base64_header.$base64_payload", 
            JWT_SECRET, 
            true
        );
        
        $base64_signature = base64_encode($signature);
        
        return "$base64_header.$base64_payload.$base64_signature";
    }
    
    // Check if user has permission
    public function hasPermission($token, $required_role = 'user') {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return false;
        }
        
        $role_hierarchy = ['user' => 0, 'admin' => 1];
        
        $user_role = $payload['role'] ?? 'user';
        $required_role_level = $role_hierarchy[$required_role] ?? 0;
        $user_role_level = $role_hierarchy[$user_role] ?? 0;
        
        return $user_role_level >= $required_role_level;
    }
}