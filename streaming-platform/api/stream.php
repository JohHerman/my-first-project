<?php
// ============================================
// STREAMING API ENDPOINT
// Streaming Platform Backend System
// ============================================

// Load configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Set headers
set_security_headers();
set_cors_headers();

// Check if video ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Video ID is required']);
    exit;
}

$video_id = intval($_GET['id']);

try {
    // Load services
    require_once __DIR__ . '/../app/services/StreamingService.php';
    require_once __DIR__ . '/../app/core/Database.php';
    
    // Initialize services
    $streamingService = new StreamingService();
    
    // Get video stream information
    $stream_info = $streamingService->streamVideo($video_id);
    
    // Handle range request if present
    if (isset($_SERVER['HTTP_RANGE'])) {
        list($start, $end, $length) = $streamingService->handleRangeRequest(
            $stream_info['file_path'],
            $stream_info['file_size']
        );
        
        $streamingService->sendVideoFile($stream_info['file_path'], $start, $end, $length);
    } else {
        // Send full file
        $streamingService->sendFullVideoFile($stream_info['file_path'], $stream_info['file_size']);
    }
    
} catch (Exception $e) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}