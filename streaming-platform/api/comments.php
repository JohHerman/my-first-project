<?php
require_once '../config/functions.php';

session_start();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        $video_id = $_POST['video_id'] ?? 0;
        $comment = sanitize($_POST['comment'] ?? '');
        
        if ($video_id && $comment) {
            $stmt = $db->prepare("
                INSERT INTO comments (video_id, user_id, comment)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$video_id, $_SESSION['user_id'], $comment]);
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>