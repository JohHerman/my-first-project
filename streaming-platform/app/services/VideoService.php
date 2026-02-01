<?php
// ============================================
// VIDEO SERVICE
// Streaming Platform Backend System
// ============================================

// ADD THESE REQUIRED FILES
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Video.php';

class VideoService {
    private $videoModel;
    private $db;
    
    public function __construct() {
        $this->videoModel = new Video();
        $this->db = Database::getInstance();
    }
    
    // Handle video upload
    public function uploadVideo($user_id, $file, $title, $description = '') {
        // Validate file
        $validation_result = $this->validateVideoFile($file);
        
        if ($validation_result !== true) {
            throw new Exception(implode(', ', $validation_result));
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = 'video_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
        $upload_path = UPLOAD_DIR . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Failed to save uploaded file");
        }
        
        // Get video information (simplified)
        $video_info = $this->getVideoInfo($upload_path);
        
        // Create thumbnail
        $thumbnail_filename = $this->createThumbnail($unique_filename);
        
        // Save to database
        $video_id = $this->videoModel->create([
            'title' => $title,
            'description' => $description,
            'filename' => $unique_filename,
            'file_size' => $file['size'],
            'duration' => $video_info['duration'],
            'thumbnail' => $thumbnail_filename,
            'user_id' => $user_id,
            'status' => 'ready'
        ]);
        
        // Move to videos directory for streaming
        $this->prepareForStreaming($video_id, $upload_path);
        
        return [
            'success' => true,
            'message' => 'Video uploaded successfully',
            'video_id' => $video_id,
            'title' => $title,
            'filename' => $unique_filename,
            'stream_url' => APP_URL . "/api/stream.php?id=" . $video_id,
            'thumbnail_url' => APP_URL . "/uploads/" . $thumbnail_filename
        ];
    }
    
    // Validate video file
    private function validateVideoFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload failed";
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = "File too large. Maximum size is " . 
                round(MAX_UPLOAD_SIZE / 1024 / 1024 / 1024, 2) . "GB";
        }
        
        // Check file type by extension
        $allowed_extensions = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mime_types = [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska'
        ];
        
        if (!in_array($mime_type, $allowed_mime_types)) {
            $errors[] = "Invalid MIME type: $mime_type";
        }
        
        return empty($errors) ? true : $errors;
    }
    
    // Get video information (simplified - in production use FFmpeg)
    private function getVideoInfo($file_path) {
        // This is a simplified version
        // In production, you would use FFmpeg to get actual duration
        
        return [
            'duration' => 180, // Default 3 minutes
            'mime_type' => mime_content_type($file_path)
        ];
    }
    
    // Create thumbnail from video
    private function createThumbnail($filename) {
        $thumbnail_name = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $thumbnail_path = UPLOAD_DIR . $thumbnail_name;
        
        // Check if GD library is available
        if (!function_exists('imagecreatetruecolor')) {
            // Create a simple text file as fallback
            file_put_contents($thumbnail_path, "Thumbnail for: $filename");
            return $thumbnail_name;
        }
        
        // Create a simple thumbnail (in production, use FFmpeg to extract frame)
        $image = imagecreatetruecolor(320, 180);
        
        // Generate gradient background
        for ($i = 0; $i < 180; $i++) {
            $color = imagecolorallocate($image, 
                rand(30, 60), 
                rand(60, 100), 
                rand(100, 150)
            );
            imageline($image, 0, $i, 320, $i, $color);
        }
        
        // Add text
        $text_color = imagecolorallocate($image, 255, 255, 255);
        $text = "VIDEO PREVIEW";
        $font = 5;
        $text_width = imagefontwidth($font) * strlen($text);
        $text_height = imagefontheight($font);
        
        $x = (320 - $text_width) / 2;
        $y = (180 - $text_height) / 2;
        
        imagestring($image, $font, $x, $y, $text, $text_color);
        
        // Save thumbnail
        imagejpeg($image, $thumbnail_path, 90);
        imagedestroy($image);
        
        return $thumbnail_name;
    }
    
    // Prepare video for streaming
    private function prepareForStreaming($video_id, $source_path) {
        $destination_path = VIDEO_DIR . "video_{$video_id}.mp4";
        
        // Simple copy for now
        if (copy($source_path, $destination_path)) {
            // Remove from uploads directory
            unlink($source_path);
        }
        
        // In production: Transcode to multiple resolutions, create HLS, etc.
    }
    
    // Get video details for playback
    public function getVideoForPlayback($video_id, $user_id = null) {
        $video = $this->videoModel->find($video_id);
        
        if (!$video) {
            throw new Exception("Video not found");
        }
        
        // Check if video file exists
        $video_file = VIDEO_DIR . "video_{$video_id}.mp4";
        if (!file_exists($video_file)) {
            // Try original upload
            $video_file = UPLOAD_DIR . $video['filename'];
            if (!file_exists($video_file)) {
                throw new Exception("Video file not available");
            }
        }
        
        // Record watch if user is logged in
        if ($user_id) {
            $this->videoModel->recordWatch($user_id, $video_id);
        }
        
        // Add streaming URL
        $video['stream_url'] = APP_URL . "/api/stream.php?id=" . $video_id;
        $video['thumbnail_url'] = $video['thumbnail'] ? 
            APP_URL . "/uploads/" . $video['thumbnail'] : null;
        $video['file_exists'] = file_exists($video_file);
        $video['file_size_formatted'] = $this->formatFileSize($video['file_size']);
        $video['duration_formatted'] = $this->formatDuration($video['duration']);
        
        return $video;
    }
    
    // Get video list with pagination
    public function getVideoList($page = 1, $limit = 20, $user_id = null) {
        $videos = $this->videoModel->getAll($page, $limit);
        $total_videos = $this->videoModel->count();
        
        // Add URLs to each video
        foreach ($videos as &$video) {
            $video['stream_url'] = APP_URL . "/api/stream.php?id=" . $video['id'];
            $video['thumbnail_url'] = $video['thumbnail'] ? 
                APP_URL . "/uploads/" . $video['thumbnail'] : null;
            $video['duration_formatted'] = $this->formatDuration($video['duration']);
        }
        
        return [
            'success' => true,
            'videos' => $videos,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total_items' => (int)$total_videos,
                'total_pages' => ceil($total_videos / $limit)
            ]
        ];
    }
    
    // Format file size
    private function formatFileSize($bytes) {
        if ($bytes == 0) return "0 B";
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
    
    // Search videos
    public function searchVideos($query, $page = 1, $limit = 20) {
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
    
    // Get popular videos
    public function getPopularVideos($limit = 10) {
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
    
    // Get videos by user
    public function getVideosByUser($user_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        return $this->db->fetchAll(
            "SELECT * FROM videos 
             WHERE user_id = ? AND status = 'ready'
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$user_id, $limit, $offset]
        );
    }
    
    // Format duration
    private function formatDuration($seconds) {
        if ($seconds < 60) {
            return $seconds . ' sec';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remaining = $seconds % 60;
            return $minutes . ' min ' . $remaining . ' sec';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' hr ' . $minutes . ' min';
        }
    }
}