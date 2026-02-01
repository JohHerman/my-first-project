<?php
// ============================================
// STREAMING SERVICE
// Streaming Platform Backend System
// ============================================

class StreamingService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Stream video file
    public function streamVideo($video_id) {
        // Get video information
        $video = $this->db->fetchOne(
            "SELECT filename, status FROM videos WHERE id = ?",
            [$video_id]
        );
        
        if (!$video || $video['status'] !== 'ready') {
            throw new Exception("Video not available");
        }
        
        // Check file paths
        $video_path = VIDEO_DIR . "video_{$video_id}.mp4";
        $upload_path = UPLOAD_DIR . $video['filename'];
        
        if (file_exists($video_path)) {
            $file_path = $video_path;
        } elseif (file_exists($upload_path)) {
            $file_path = $upload_path;
        } else {
            throw new Exception("Video file not found");
        }
        
        // Get file information
        $file_size = filesize($file_path);
        $mime_type = $this->getMimeType($file_path);
        
        return [
            'file_path' => $file_path,
            'file_size' => $file_size,
            'mime_type' => $mime_type
        ];
    }
    
    // Get MIME type for video file
    private function getMimeType($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        $mime_types = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska'
        ];
        
        return $mime_types[$extension] ?? 'application/octet-stream';
    }
    
    // Handle range requests for streaming
    public function handleRangeRequest($file_path, $file_size) {
        $range = $_SERVER['HTTP_RANGE'] ?? '';
        
        if (empty($range)) {
            return [0, $file_size - 1, $file_size];
        }
        
        // Parse range header
        $range = str_replace('bytes=', '', $range);
        list($start, $end) = explode('-', $range);
        
        $start = intval($start);
        $end = $end === '' ? $file_size - 1 : intval($end);
        
        // Validate range
        if ($start >= $file_size || $end >= $file_size || $start > $end) {
            http_response_code(416);
            header("Content-Range: bytes */$file_size");
            exit;
        }
        
        $length = $end - $start + 1;
        
        return [$start, $end, $length];
    }
    
    // Send video file with range support
    public function sendVideoFile($file_path, $start, $end, $length) {
        // Set headers
        http_response_code(206);
        header("Content-Type: " . $this->getMimeType($file_path));
        header("Content-Length: $length");
        header("Content-Range: bytes $start-$end/$length");
        header("Accept-Ranges: bytes");
        header("Cache-Control: public, max-age=31536000");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        
        // Open file
        $fp = fopen($file_path, 'rb');
        fseek($fp, $start);
        
        // Send file in chunks
        $buffer_size = 8192;
        $bytes_sent = 0;
        
        while (!feof($fp) && $bytes_sent < $length && connection_status() == 0) {
            $bytes_to_read = min($buffer_size, $length - $bytes_sent);
            echo fread($fp, $bytes_to_read);
            $bytes_sent += $bytes_to_read;
            flush();
        }
        
        fclose($fp);
    }
    
    // Send entire video file
    public function sendFullVideoFile($file_path, $file_size) {
        header("Content-Type: " . $this->getMimeType($file_path));
        header("Content-Length: $file_size");
        header("Accept-Ranges: bytes");
        header("Cache-Control: public, max-age=31536000");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        
        readfile($file_path);
    }
}