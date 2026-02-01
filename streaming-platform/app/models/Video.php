<?php
// ============================================
// VIDEO MODEL
// Streaming Platform Backend System
// ============================================

class Video {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Create new video
    public function create($data) {
        // Validate required fields
        if (empty($data['title']) || empty($data['filename']) || empty($data['user_id'])) {
            throw new Exception("Title, filename, and user_id are required");
        }
        
        $video_id = $this->db->insert('videos', [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'filename' => $data['filename'],
            'file_size' => $data['file_size'] ?? 0,
            'duration' => $data['duration'] ?? 0,
            'thumbnail' => $data['thumbnail'] ?? '',
            'user_id' => $data['user_id'],
            'status' => $data['status'] ?? 'processing',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $video_id;
    }
    
    // Get video by ID
    public function find($id) {
        $video = $this->db->fetchOne(
            "SELECT v.*, u.username as uploader, u.email as uploader_email 
             FROM videos v 
             LEFT JOIN users u ON v.user_id = u.id 
             WHERE v.id = ?",
            [$id]
        );
        
        if ($video) {
            // Get view count
            $view_count = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM watch_history WHERE video_id = ?",
                [$id]
            );
            $video['view_count'] = $view_count['count'];
        }
        
        return $video;
    }
    
    // Get all videos with pagination
    public function getAll($page = 1, $limit = 20, $status = 'ready') {
        $offset = ($page - 1) * $limit;
        
        return $this->db->fetchAll(
            "SELECT v.*, u.username as uploader 
             FROM videos v 
             LEFT JOIN users u ON v.user_id = u.id 
             WHERE v.status = ? 
             ORDER BY v.created_at DESC 
             LIMIT ? OFFSET ?",
            [$status, $limit, $offset]
        );
    }
    
    // Get videos by user
    public function getByUser($user_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        return $this->db->fetchAll(
            "SELECT * FROM videos 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$user_id, $limit, $offset]
        );
    }
    
    // Update video
    public function update($id, $data) {
        $update_data = [];
        
        if (isset($data['title'])) {
            $update_data['title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $update_data['description'] = $data['description'];
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }
        if (isset($data['views'])) {
            $update_data['views'] = $data['views'];
        }
        if (isset($data['thumbnail'])) {
            $update_data['thumbnail'] = $data['thumbnail'];
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->update('videos', $update_data, 'id = ?', [$id]);
        }
        
        return 0;
    }
    
    // Increment view count
    public function incrementViews($id) {
        return $this->db->query(
            "UPDATE videos SET views = views + 1 WHERE id = ?",
            [$id]
        );
    }
    
    // Delete video
    public function delete($id) {
        return $this->db->delete('videos', 'id = ?', [$id]);
    }
    
    // Count total videos
    public function count($status = 'ready') {
        return $this->db->count('videos', 'status = ?', [$status]);
    }
    
    // Search videos
    public function search($query, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $search_query = "%$query%";
        
        return $this->db->fetchAll(
            "SELECT v.*, u.username as uploader 
             FROM videos v 
             LEFT JOIN users u ON v.user_id = u.id 
             WHERE v.status = 'ready' 
             AND (v.title LIKE ? OR v.description LIKE ?) 
             ORDER BY v.created_at DESC 
             LIMIT ? OFFSET ?",
            [$search_query, $search_query, $limit, $offset]
        );
    }
    
    // Record watch history
    public function recordWatch($user_id, $video_id, $progress_seconds = 0) {
        // Check if already watched recently
        $existing = $this->db->fetchOne(
            "SELECT id FROM watch_history 
             WHERE user_id = ? AND video_id = ? 
             AND watched_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$user_id, $video_id]
        );
        
        if (!$existing) {
            $completed = ($progress_seconds >= 60); // Mark as completed if watched > 60 seconds
            
            $this->db->insert('watch_history', [
                'user_id' => $user_id,
                'video_id' => $video_id,
                'progress_seconds' => $progress_seconds,
                'completed' => $completed ? 1 : 0,
                'watched_at' => date('Y-m-d H:i:s')
            ]);
            
            // Increment view count
            $this->incrementViews($video_id);
        }
        
        return true;
    }
    
    // Get popular videos
    public function getPopular($limit = 10) {
        return $this->db->fetchAll(
            "SELECT v.*, u.username as uploader 
             FROM videos v 
             LEFT JOIN users u ON v.user_id = u.id 
             WHERE v.status = 'ready' 
             ORDER BY v.views DESC, v.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    // Get recent videos
    public function getRecent($limit = 10) {
        return $this->db->fetchAll(
            "SELECT v.*, u.username as uploader 
             FROM videos v 
             LEFT JOIN users u ON v.user_id = u.id 
             WHERE v.status = 'ready' 
             ORDER BY v.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
}