<?php
// ============================================
// VIDEO CONTROLLER
// Streaming Platform Backend System
// ============================================

// REMOVE the direct Video model require - we'll use VideoService instead
require_once __DIR__ . '/../services/VideoService.php';
require_once __DIR__ . '/../services/StreamingService.php';
require_once __DIR__ . '/../core/Auth.php';

class VideoController {
    private $videoService;
    private $streamingService;
    private $auth;
    
    public function __construct() {
        $this->videoService = new VideoService();
        $this->streamingService = new StreamingService();
        $this->auth = new Auth();
    }
    
    // List all videos
    public function index() {
        try {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            // Validate pagination
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 20;
            
            // Get user ID if authenticated
            $user_id = $this->getUserIdFromToken();
            
            // Get video list
            $result = $this->videoService->getVideoList($page, $limit, $user_id);
            
            $this->sendJsonResponse(200, $result);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 400);
        }
    }
    
    // Get single video
    public function show($id) {
        try {
            if (!is_numeric($id) || $id < 1) {
                throw new Exception("Invalid video ID");
            }
            
            // Get user ID if authenticated
            $user_id = $this->getUserIdFromToken();
            
            // Get video details
            $video = $this->videoService->getVideoForPlayback($id, $user_id);
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'video' => $video
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 404);
        }
    }
    
    // Upload video
    public function upload() {
        try {
            // Check authentication
            $user = $this->getAuthenticatedUser();
            
            // Check request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Method not allowed");
            }
            
            // Check if file was uploaded
            if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No video file uploaded or upload error");
            }
            
            // Check if title is provided
            if (empty($_POST['title'])) {
                throw new Exception("Video title is required");
            }
            
            // Upload video
            $result = $this->videoService->uploadVideo(
                $user['id'],
                $_FILES['video'],
                $_POST['title'],
                $_POST['description'] ?? ''
            );
            
            $this->sendJsonResponse(201, $result);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 400);
        }
    }
    
    // Search videos - FIXED: Use VideoService
    public function search() {
        try {
            $query = isset($_GET['q']) ? trim($_GET['q']) : '';
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            if (empty($query)) {
                throw new Exception("Search query is required");
            }
            
            // Validate pagination
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 20;
            
            // Search videos using VideoService
            $videos = $this->videoService->searchVideos($query, $page, $limit);
            
            // Add URLs
            foreach ($videos as &$video) {
                $video['stream_url'] = APP_URL . "/api/stream.php?id=" . $video['id'];
                $video['thumbnail_url'] = $video['thumbnail'] ? 
                    APP_URL . "/uploads/" . $video['thumbnail'] : null;
            }
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'query' => $query,
                'videos' => $videos,
                'count' => count($videos)
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 400);
        }
    }
    
    // Get popular videos - FIXED: Use VideoService
    public function popular() {
        try {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            if ($limit < 1 || $limit > 50) $limit = 10;
            
            // Get popular videos using VideoService
            $videos = $this->videoService->getPopularVideos($limit);
            
            // Add URLs
            foreach ($videos as &$video) {
                $video['stream_url'] = APP_URL . "/api/stream.php?id=" . $video['id'];
                $video['thumbnail_url'] = $video['thumbnail'] ? 
                    APP_URL . "/uploads/" . $video['thumbnail'] : null;
            }
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'videos' => $videos
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 400);
        }
    }
    
    // Get user's videos - FIXED: Use VideoService
    public function myVideos() {
        try {
            // Check authentication
            $user = $this->getAuthenticatedUser();
            
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            // Validate pagination
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 20;
            
            // Get user's videos using VideoService
            $videos = $this->videoService->getVideosByUser($user['id'], $page, $limit);
            
            // Add URLs
            foreach ($videos as &$video) {
                $video['stream_url'] = APP_URL . "/api/stream.php?id=" . $video['id'];
                $video['thumbnail_url'] = $video['thumbnail'] ? 
                    APP_URL . "/uploads/" . $video['thumbnail'] : null;
            }
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'videos' => $videos
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError($e->getMessage(), 401);
        }
    }
    
    // Helper: Get user ID from token
    private function getUserIdFromToken() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (!empty($auth_header) && preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            $token = $matches[1];
            $payload = $this->auth->validateToken($token);
            if ($payload) {
                return $payload['user_id'];
            }
        }
        
        return null;
    }
    
    // Helper: Get authenticated user
    private function getAuthenticatedUser() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (empty($auth_header) || !preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            throw new Exception("Authentication required");
        }
        
        $token = $matches[1];
        return $this->auth->getCurrentUser($token);
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