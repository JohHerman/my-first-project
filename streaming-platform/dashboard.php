<?php
session_start();
require_once 'config/functions.php';
requireUser(); // Only logged in users can access

// Check if banned
if (isBanned()) {
    session_destroy();
    redirect('login.php?message=banned');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get user videos
$videos = $db->query("
    SELECT * FROM videos 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
")->fetchAll();

// Get stats
$total_views = $db->query("SELECT SUM(views) as total FROM videos WHERE user_id = $user_id")->fetch()['total'] ?? 0;
$total_videos = count($videos);

// Get user info
$user = $db->query("SELECT * FROM users WHERE id = $user_id")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - StreamHub</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }
        .admin-badge {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .tab-nav {
            display: flex;
            border-bottom: 2px solid #eee;
            margin: 20px 0;
        }
        .tab-nav a {
            padding: 10px 20px;
            text-decoration: none;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .tab-nav a.active {
            color: #ff0000;
            border-bottom-color: #ff0000;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main class="dashboard-container">
        <div class="user-header">
            <div class="user-info">
                <img src="<?= getProfilePicUrl($user['profile_pic']) ?>" 
                     class="user-avatar-large" 
                     alt="<?= $user['username'] ?>">
                <div>
                    <h1><?= htmlspecialchars($user['username']) ?>
                        <?php if($user['is_admin']): ?>
                            <span class="admin-badge">ADMIN</span>
                        <?php endif; ?>
                    </h1>
                    <p><?= $user['email'] ?></p>
                    <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="quick-actions">
                <a href="upload.php" class="btn-primary">
                    <i class="fas fa-upload"></i> Upload Video
                </a>
                <a href="profile.php" class="btn-action" style="background: white; color: #333;">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <?php if($user['is_admin']): ?>
                <a href="admin/dashboard.php" class="btn-action" style="background: #ff4757; color: white;">
                    <i class="fas fa-user-shield"></i> Admin Panel
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="tab-nav">
            <a href="#videos" class="active">My Videos</a>
            <a href="#analytics">Analytics</a>
            <a href="#settings">Settings</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-video"></i> Total Videos</h3>
                <p class="stat-number"><?= $total_videos ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-eye"></i> Total Views</h3>
                <p class="stat-number"><?= number_format($total_views) ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-calendar"></i> Joined</h3>
                <p class="stat-number"><?= date('M Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
        
        <section id="videos" class="my-videos">
            <h2><i class="fas fa-film"></i> My Videos</h2>
            <?php if($total_videos > 0): ?>
                <div class="video-grid">
                    <?php foreach($videos as $video): ?>
                    <div class="video-card">
                        <a href="watch.php?id=<?= $video['id'] ?>">
                            <div class="thumbnail">
                                <img src="<?= getThumbnailUrl($video['thumbnail']) ?>" 
                                     alt="<?= $video['title'] ?>">
                                <span class="duration"><?= formatDuration($video['duration']) ?></span>
                            </div>
                            <div class="video-info">
                                <h3><?= htmlspecialchars($video['title']) ?></h3>
                                <p class="meta">
                                    <i class="fas fa-eye"></i> <?= number_format($video['views']) ?> views • 
                                    <i class="far fa-clock"></i> <?= getRelativeTime($video['created_at']) ?>
                                </p>
                            </div>
                        </a>
                        <div class="video-actions">
                            <a href="watch.php?id=<?= $video['id'] ?>" class="btn-small">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit.php?id=<?= $video['id'] ?>" class="btn-small">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button onclick="deleteVideo(<?= $video['id'] ?>)" class="btn-small btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash fa-3x"></i>
                    <h3>No videos yet</h3>
                    <p>Upload your first video and share it with the world!</p>
                    <a href="upload.php" class="btn-primary">
                        <i class="fas fa-upload"></i> Upload First Video
                    </a>
                </div>
            <?php endif; ?>
        </section>
        
        <section id="analytics" style="display: none;">
            <h2><i class="fas fa-chart-line"></i> Analytics</h2>
            <p>Coming soon! View your video performance and viewer statistics.</p>
        </section>
        
        <section id="settings" style="display: none;">
            <h2><i class="fas fa-cog"></i> Settings</h2>
            <p>Account settings and preferences.</p>
        </section>
    </main>

    <script>
    // Tab navigation
    document.querySelectorAll('.tab-nav a').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            document.querySelectorAll('.tab-nav a').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            document.querySelectorAll('section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(targetId).style.display = 'block';
        });
    });
    
    // Delete video function
    function deleteVideo(videoId) {
        if (confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
            fetch(`api/videos.php?id=${videoId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete video: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete video');
            });
        }
    }
    </script>
</body>
</html>