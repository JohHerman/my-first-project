<?php
session_start();
require_once '../config/functions.php';
requireAdmin();

$db = getDB();
$message = '';

// Handle video deletion
if (isset($_GET['delete'])) {
    $video_id = (int)$_GET['delete'];
    
    // Get video info
    $video = $db->query("SELECT * FROM videos WHERE id = $video_id")->fetch();
    
    if ($video) {
        // Delete video file
        $video_path = '../' . VIDEO_PATH . $video['filename'];
        $thumb_path = '../' . THUMB_PATH . $video['thumbnail'];
        
        if (file_exists($video_path)) unlink($video_path);
        if (file_exists($thumb_path) && $video['thumbnail'] != 'default-thumbnail.jpg') {
            unlink($thumb_path);
        }
        
        // Delete from database
        $db->query("DELETE FROM videos WHERE id = $video_id");
        $db->query("DELETE FROM comments WHERE video_id = $video_id");
        
        $message = 'Video deleted successfully';
    }
}

// Get all videos with user info
$videos = $db->query("
    SELECT v.*, u.username, u.email 
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .video-management { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
        .video-list { background: white; border-radius: 8px; padding: 20px; }
        .video-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; }
        .video-item:hover { background: #f8f9fa; }
        .video-item.active { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .video-preview { background: white; border-radius: 8px; padding: 20px; }
        .video-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center; }
        .admin-actions { display: flex; gap: 10px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-video"></i> Manage Videos</h1>
                <p>Total: <?= count($videos) ?> videos</p>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <div class="video-management">
                <div class="video-list">
                    <input type="text" placeholder="Search videos..." style="width: 100%; padding: 10px; margin-bottom: 20px;">
                    
                    <?php foreach($videos as $video): ?>
                    <div class="video-item" onclick="selectVideo(<?= $video['id'] ?>)">
                        <strong><?= htmlspecialchars(substr($video['title'], 0, 30)) ?></strong>
                        <p style="font-size: 0.9em; color: #666;">
                            By: <?= htmlspecialchars($video['username']) ?><br>
                            Views: <?= number_format($video['views']) ?> • 
                            <?= date('M d', strtotime($video['created_at'])) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="video-preview" id="videoDetails">
                    <h3>Select a video to view details</h3>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function selectVideo(videoId) {
        // Update active item
        document.querySelectorAll('.video-item').forEach(item => {
            item.classList.remove('active');
        });
        event.target.closest('.video-item').classList.add('active');
        
        // Load video details via AJAX
        fetch(`../api/admin.php?action=get_video&id=${videoId}`)
            .then(response => response.json())
            .then(video => {
                const videoDetails = document.getElementById('videoDetails');
                videoDetails.innerHTML = `
                    <h2>${video.title}</h2>
                    <div class="video-stats-grid">
                        <div class="stat-box">
                            <strong>Views</strong><br>
                            ${video.views.toLocaleString()}
                        </div>
                        <div class="stat-box">
                            <strong>Duration</strong><br>
                            ${formatDuration(video.duration)}
                        </div>
                        <div class="stat-box">
                            <strong>Uploaded</strong><br>
                            ${new Date(video.created_at).toLocaleDateString()}
                        </div>
                    </div>
                    
                    <p><strong>Description:</strong><br>${video.description}</p>
                    <p><strong>Category:</strong> ${video.category}</p>
                    <p><strong>Uploaded by:</strong> ${video.username} (${video.email})</p>
                    
                    <div class="admin-actions">
                        <a href="../watch.php?id=${video.id}" target="_blank" class="btn-admin">
                            <i class="fas fa-eye"></i> View Video
                        </a>
                        <button onclick="deleteVideo(${video.id})" class="btn-admin btn-danger">
                            <i class="fas fa-trash"></i> Delete Video
                        </button>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <video controls width="100%" poster="../uploads/thumbnails/${video.thumbnail}">
                            <source src="../uploads/videos/${video.filename}" type="video/mp4">
                        </video>
                    </div>
                `;
            });
    }
    
    function formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
    
    function deleteVideo(videoId) {
        if (confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
            window.location.href = `videos.php?delete=${videoId}`;
        }
    }
    </script>
</body>
</html>