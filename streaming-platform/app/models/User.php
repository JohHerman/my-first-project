<?php
// ============================================
// USER MODEL
// Streaming Platform Backend System
// ============================================

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Find user by ID
    public function find($id) {
        return $this->db->fetchOne(
            "SELECT id, email, username, full_name, role, status, 
                    last_login, created_at, updated_at 
             FROM users WHERE id = ?",
            [$id]
        );
    }
    
    // Find user by email
    public function findByEmail($email) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }
    
    // Find user by username
    public function findByUsername($username) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
    }
    
    // Create new user
    public function create($data) {
        // Validate required fields
        if (empty($data['email']) || empty($data['password']) || empty($data['username'])) {
            throw new Exception("Email, password, and username are required");
        }
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $user_id = $this->db->insert('users', [
            'email' => $data['email'],
            'username' => $data['username'],
            'password_hash' => $password_hash,
            'full_name' => $data['full_name'] ?? '',
            'role' => $data['role'] ?? 'user',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $user_id;
    }
    
    // Update user
    public function update($id, $data) {
        $update_data = [];
        
        if (isset($data['email'])) {
            $update_data['email'] = $data['email'];
        }
        if (isset($data['username'])) {
            $update_data['username'] = $data['username'];
        }
        if (isset($data['full_name'])) {
            $update_data['full_name'] = $data['full_name'];
        }
        if (isset($data['password'])) {
            $update_data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->update('users', $update_data, 'id = ?', [$id]);
        }
        
        return 0;
    }
    
    // Delete user
    public function delete($id) {
        return $this->db->delete('users', 'id = ?', [$id]);
    }
    
    // Get all users with pagination
    public function getAll($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        return $this->db->fetchAll(
            "SELECT id, email, username, full_name, role, status, 
                    last_login, created_at 
             FROM users 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    // Count total users
    public function count() {
        return $this->db->count('users');
    }
    
    // Get user's subscription
    public function getSubscription($user_id) {
        return $this->db->fetchOne(
            "SELECT * FROM subscriptions 
             WHERE user_id = ? 
             AND status = 'active' 
             AND (expires_at IS NULL OR expires_at > NOW())",
            [$user_id]
        );
    }
    
    // Get user's watch history
    public function getWatchHistory($user_id, $limit = 20) {
        return $this->db->fetchAll(
            "SELECT wh.*, v.title, v.thumbnail, v.duration 
             FROM watch_history wh 
             JOIN videos v ON wh.video_id = v.id 
             WHERE wh.user_id = ? 
             ORDER BY wh.watched_at DESC 
             LIMIT ?",
            [$user_id, $limit]
        );
    }
}