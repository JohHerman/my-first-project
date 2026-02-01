<?php
require_once '../config/functions.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $video_id = $_GET['id'] ?? 0;
        if ($video_id) {
            $video = $db->query("SELECT * FROM videos WHERE id = $video_id")->fetch();
            echo json_encode($video);
        } else {
            $videos = $db->query("SELECT * FROM videos ORDER BY created_at DESC LIMIT 20")->fetchAll();
            echo json_encode($videos);
        }
        break;
        
    case 'DELETE':
        session_start();
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $video_id = $_GET['id'] ?? 0;
        if ($video_id) {
            // Check if user owns the video
            $video = $db->query("SELECT * FROM videos WHERE id = $video_id")->fetch();
            if ($video['user_id'] == $_SESSION['user_id']) {
                // Delete video file
                $video_path = VIDEO_PATH . $video['filename'];
                $thumb_path = THUMB_PATH . $video['thumbnail'];
                
                if (file_exists($video_path)) unlink($video_path);
                if (file_exists($thumb_path) && $video['thumbnail'] != 'default-thumbnail.jpg') {
                    unlink($thumb_path);
                }
                
                $db->query("DELETE FROM videos WHERE id = $video_id");
                echo json_encode(['success' => true]);
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>