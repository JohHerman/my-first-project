<?php
require_once '../config/functions.php';

session_start();
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

switch($action) {
    case 'get_video':
        if ($method === 'GET') {
            $video_id = (int)$_GET['id'];
            $video = $db->query("
                SELECT v.*, u.username, u.email 
                FROM videos v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.id = $video_id
            ")->fetch();
            
            if ($video) {
                echo json_encode($video);
            } else {
                echo json_encode(['error' => 'Video not found']);
            }
        }
        break;
        
    case 'get_user_stats':
        if ($method === 'GET') {
            $user_id = (int)$_GET['id'];
            
            $stats = $db->query("
                SELECT 
                    (SELECT COUNT(*) FROM videos WHERE user_id = $user_id) as video_count,
                    (SELECT SUM(views) FROM videos WHERE user_id = $user_id) as total_views,
                    (SELECT COUNT(*) FROM comments WHERE user_id = $user_id) as comment_count,
                    (SELECT created_at FROM users WHERE id = $user_id) as join_date
            ")->fetch();
            
            echo json_encode($stats);
        }
        break;
        
    case 'resolve_report':
        if ($method === 'GET') {
            $report_id = (int)$_GET['id'];
            $db->query("UPDATE reports SET status = 'resolved' WHERE id = $report_id");
            echo json_encode(['success' => true]);
        }
        break;
        
    case 'dismiss_report':
        if ($method === 'GET') {
            $report_id = (int)$_GET['id'];
            $db->query("UPDATE reports SET status = 'dismissed' WHERE id = $report_id");
            echo json_encode(['success' => true]);
        }
        break;
        
    case 'get_dashboard_stats':
        if ($method === 'GET') {
            $stats = [];
            
            // Total users
            $stats['total_users'] = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
            
            // Total videos
            $stats['total_videos'] = $db->query("SELECT COUNT(*) as count FROM videos")->fetch()['count'];
            
            // Total views
            $stats['total_views'] = $db->query("SELECT SUM(views) as total FROM videos")->fetch()['total'] ?? 0;
            
            // New users today
            $stats['new_users_today'] = $db->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
            
            // New videos today
            $stats['new_videos_today'] = $db->query("SELECT COUNT(*) as count FROM videos WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
            
            // Pending reports
            $stats['pending_reports'] = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch()['count'];
            
            echo json_encode($stats);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
}
?>