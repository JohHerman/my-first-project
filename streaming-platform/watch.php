<?php
session_start();
require_once 'config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$db = getDB();
$video_id = (int)$_GET['id'];

// Increment view count
$db->query("UPDATE videos SET views = views + 1 WHERE id = $video_id");

$video = $db->query("
    SELECT v.*, u.username, u.profile_pic 
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.id = $video_id
")->fetch();

if (!$video) {
    die("Video not found");
}

// Get comments
$comments = $db->query("
    SELECT c.*, u.username, u.profile_pic 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.video_id = $video_id 
    ORDER BY c.created_at DESC
")->fetchAll();

// Get related videos
$related = $db->query("
    SELECT v.*, u.username, u.profile_pic 
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.category = '{$video['category']}' AND v.id != $video_id 
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - StreamHub</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/player.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main class="watch-container">
        <div class="player-section">
            <div class="video-player">
                <video id="main-video" controls poster="<?= getThumbnailUrl($video['thumbnail']) ?>">
                    <source src="<?= getVideoUrl($video['filename']) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            
            <div class="video-details">
                <h1><?= htmlspecialchars($video['title']) ?></h1>
                <div class="video-stats">
                    <span><?= number_format($video['views']) ?> views</span>
                    <span>•</span>
                    <span><?= getRelativeTime($video['created_at']) ?></span>
                </div>
                
                <div class="video-actions">
                    <button class="btn-action" onclick="likeVideo()">
                        <i class="far fa-thumbs-up"></i> Like
                    </button>
                    <button class="btn-action" onclick="shareVideo()">
                        <i class="fas fa-share"></i> Share
                    </button>
                    <?php if(isLoggedIn() && $_SESSION['user_id'] == $video['user_id']): ?>
                    <button class="btn-action" onclick="deleteVideo(<?= $video['id'] ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                    <button class="btn-action" onclick="downloadVideo('<?= getVideoUrl($video['filename']) ?>')">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
                
                <div class="channel-info">
                    <img src="<?= getProfilePicUrl($video['profile_pic']) ?>" alt="<?= $video['username'] ?>">
                    <div>
                        <h3><?= htmlspecialchars($video['username']) ?></h3>
                        <p>Published on <?= date('F j, Y', strtotime($video['created_at'])) ?></p>
                    </div>
                </div>
                
                <div class="video-description">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($video['description'])) ?></p>
                </div>
            </div>
            
            <div class="comments-section">
                <h3>Comments (<?= count($comments) ?>)</h3>
                <?php if(isLoggedIn()): ?>
                <form class="comment-form" method="POST" action="api/comments.php">
                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                    <textarea name="comment" placeholder="Add a comment..." required></textarea>
                    <button type="submit" class="btn-primary">Comment</button>
                </form>
                <?php endif; ?>
                
                <div class="comments-list">
                    <?php foreach($comments as $comment): ?>
                    <div class="comment">
                        <img src="<?= getProfilePicUrl($comment['profile_pic']) ?>" alt="<?= $comment['username'] ?>">
                        <div>
                            <h4><?= htmlspecialchars($comment['username']) ?></h4>
                            <p><?= htmlspecialchars($comment['comment']) ?></p>
                            <small><?= getRelativeTime($comment['created_at']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="sidebar">
            <h3>Related Videos</h3>
            <?php foreach($related as $rel): ?>
            <a href="watch.php?id=<?= $rel['id'] ?>" class="related-video">
                <img src="<?= getThumbnailUrl($rel['thumbnail']) ?>" alt="<?= $rel['title'] ?>">
                <div>
                    <h4><?= htmlspecialchars($rel['title']) ?></h4>
                    <p><?= htmlspecialchars($rel['username']) ?></p>
                    <p><?= number_format($rel['views']) ?> views • <?= getRelativeTime($rel['created_at']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="assets/js/player.js"></script>
</body>
</html>