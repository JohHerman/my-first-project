<?php
session_start();
require_once 'config/functions.php';

$db = getDB();

// Get latest videos
$videos = $db->query("
    SELECT v.*, u.username, u.profile_pic 
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT 12
")->fetchAll();

// Get most viewed videos
$trending_videos = $db->query("
    SELECT v.*, u.username, u.profile_pic 
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.views DESC 
    LIMIT 6
")->fetchAll();

// Get statistics
$total_videos = $db->query("SELECT COUNT(*) as count FROM videos")->fetch()['count'];
$total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$total_views = $db->query("SELECT SUM(views) as total FROM videos")->fetch()['total'] ?? 0;

// Get categories with video counts
$categories = $db->query("
    SELECT category, COUNT(*) as count 
    FROM videos 
    WHERE category IS NOT NULL AND category != '' 
    GROUP BY category 
    ORDER BY count DESC 
    LIMIT 6
")->fetchAll();

// Get popular creators (users with most videos)
$top_creators = $db->query("
    SELECT u.username, u.profile_pic, COUNT(v.id) as video_count
    FROM users u
    LEFT JOIN videos v ON u.id = v.user_id
    GROUP BY u.id
    HAVING video_count > 0
    ORDER BY video_count DESC
    LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamHub - Watch & Share Videos</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            font-weight: 800;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .hero-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-large {
            padding: 15px 30px;
            font-size: 1.1rem;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 13px 28px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }
        
        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 60px 0;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 60px 0 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-header h2 {
            font-size: 2rem;
            color: #333;
            position: relative;
            padding-left: 15px;
        }
        
        .section-header h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 4px;
            background: #667eea;
            border-radius: 2px;
        }
        
        .view-all {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        /* Video Cards Enhancement */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .video-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            position: relative;
        }
        
        .video-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .video-card .thumbnail {
            position: relative;
            padding-top: 56.25%; /* 16:9 aspect ratio */
            overflow: hidden;
        }
        
        .video-card .thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .video-card:hover .thumbnail img {
            transform: scale(1.05);
        }
        
        .duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .video-info {
            padding: 20px;
        }
        
        .video-title {
            font-size: 1.1rem;
            margin-bottom: 12px;
            line-height: 1.4;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .video-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .channel {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 0.95rem;
        }
        
        .channel img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #888;
        }
        
        .stats i {
            margin-right: 5px;
        }
        
        /* Categories Section */
        .categories-section {
            margin: 60px 0;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .category-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .category-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }
        
        .category-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .category-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Creators Section */
        .creators-section {
            margin: 60px 0;
        }
        
        .creators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .creator-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .creator-card:hover {
            transform: translateY(-5px);
        }
        
        .creator-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid #f0f0f0;
        }
        
        .creator-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .creator-videos {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Featured Section */
        .featured-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 60px 40px;
            border-radius: 15px;
            margin: 60px 0;
            text-align: center;
        }
        
        .featured-content h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 20px;
        }
        
        .featured-content p {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }
        
        .featured-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .feature-item {
            text-align: center;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .feature-desc {
            color: #666;
            line-height: 1.5;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 40px 0;
        }
        
        .empty-state i {
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            color: #888;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .section-header h2::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Stream Your World</h1>
                <p class="hero-subtitle">Join millions of creators sharing their stories, talents, and passions on StreamHub - where every video finds its audience</p>
                
                <div class="hero-actions">
                    <?php if(!isLoggedIn()): ?>
                        <a href="register.php" class="btn-primary btn-large">
                            <i class="fas fa-rocket"></i> Start Streaming Free
                        </a>
                        <a href="browse.php" class="btn-secondary">
                            <i class="fas fa-play-circle"></i> Explore Content
                        </a>
                    <?php else: ?>
                        <a href="upload.php" class="btn-primary btn-large">
                            <i class="fas fa-cloud-upload-alt"></i> Share Your Creativity
                        </a>
                        <a href="dashboard.php" class="btn-secondary">
                            <i class="fas fa-chart-line"></i> View Analytics
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="stat-number"><?= number_format($total_videos) ?>+</div>
                <div class="stat-label">Videos Streamed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= number_format($total_users) ?>+</div>
                <div class="stat-label">Active Creators</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?= number_format($total_views) ?>+</div>
                <div class="stat-label">Total Views</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-number">24/7</div>
                <div class="stat-label">Streaming Worldwide</div>
            </div>
        </div>

        <!-- Trending Videos -->
        <section class="video-grid-section">
            <div class="section-header">
                <h2><i class="fas fa-fire" style="color: #ff6b6b; margin-right: 10px;"></i> Trending Now</h2>
                <a href="browse.php?sort=views" class="view-all">View All Trending <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if(count($trending_videos) > 0): ?>
                <div class="video-grid">
                    <?php foreach($trending_videos as $video): ?>
                    <div class="video-card">
                        <a href="watch.php?id=<?= $video['id'] ?>" class="video-link">
                            <div class="thumbnail">
                                <img src="<?= getThumbnailUrl($video['thumbnail']) ?>" 
                                     alt="<?= htmlspecialchars($video['title']) ?>"
                                     onerror="this.src='assets/images/default-thumbnail.jpg'">
                                <span class="duration"><?= formatDuration($video['duration']) ?></span>
                                <div style="position: absolute; top: 10px; left: 10px; background: rgba(255, 107, 107, 0.9); color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">
                                    <i class="fas fa-fire"></i> TRENDING
                                </div>
                            </div>
                            <div class="video-info">
                                <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                                <div class="video-meta">
                                    <span class="channel">
                                        <img src="<?= getProfilePicUrl($video['profile_pic']) ?>" 
                                             alt="<?= htmlspecialchars($video['username']) ?>"
                                             onerror="this.src='assets/images/default-avatar.jpg'">
                                        <?= htmlspecialchars($video['username']) ?>
                                    </span>
                                    <div class="stats">
                                        <span><i class="fas fa-eye"></i> <?= number_format($video['views']) ?> views</span>
                                        <span><i class="far fa-clock"></i> <?= getRelativeTime($video['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Featured Section -->
        <section class="featured-section">
            <div class="featured-content">
                <h2>Why Choose StreamHub?</h2>
                <p>Join a platform built for creators, by creators. From beginners to professionals, we provide everything you need to share your content with the world.</p>
                
                <div class="featured-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h3 class="feature-title">Easy Upload</h3>
                        <p class="feature-desc">Upload videos in any format with our simple, drag-and-drop interface</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Detailed Analytics</h3>
                        <p class="feature-desc">Track views, engagement, and audience growth with our powerful analytics</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="feature-title">Global Reach</h3>
                        <p class="feature-desc">Share your content with viewers worldwide and build an international audience</p>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Content Protection</h3>
                        <p class="feature-desc">Advanced copyright protection and content security features</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Latest Videos -->
        <section class="video-grid-section">
            <div class="section-header">
                <h2><i class="fas fa-clock" style="color: #4ecdc4; margin-right: 10px;"></i> Fresh Uploads</h2>
                <a href="browse.php" class="view-all">Browse All Videos <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if(count($videos) > 0): ?>
                <div class="video-grid">
                    <?php foreach($videos as $video): ?>
                    <div class="video-card">
                        <a href="watch.php?id=<?= $video['id'] ?>" class="video-link">
                            <div class="thumbnail">
                                <img src="<?= getThumbnailUrl($video['thumbnail']) ?>" 
                                     alt="<?= htmlspecialchars($video['title']) ?>"
                                     onerror="this.src='assets/images/default-thumbnail.jpg'">
                                <span class="duration"><?= formatDuration($video['duration']) ?></span>
                                <div style="position: absolute; top: 10px; left: 10px; background: rgba(78, 205, 196, 0.9); color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">
                                    <i class="fas fa-clock"></i> NEW
                                </div>
                            </div>
                            <div class="video-info">
                                <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                                <div class="video-meta">
                                    <span class="channel">
                                        <img src="<?= getProfilePicUrl($video['profile_pic']) ?>" 
                                             alt="<?= htmlspecialchars($video['username']) ?>"
                                             onerror="this.src='assets/images/default-avatar.jpg'">
                                        <?= htmlspecialchars($video['username']) ?>
                                    </span>
                                    <div class="stats">
                                        <span><i class="fas fa-eye"></i> <?= number_format($video['views']) ?> views</span>
                                        <span><i class="far fa-clock"></i> <?= getRelativeTime($video['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash fa-3x"></i>
                    <h3>No videos yet</h3>
                    <p>Be the first to upload a video and start your streaming journey!</p>
                    <?php if(isLoggedIn()): ?>
                        <a href="upload.php" class="btn-primary">Upload Your First Video</a>
                    <?php else: ?>
                        <a href="register.php" class="btn-primary">Join Now & Start Streaming</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Categories -->
        <?php if(count($categories) > 0): ?>
        <section class="categories-section">
            <div class="section-header">
                <h2><i class="fas fa-tags" style="color: #ffd166; margin-right: 10px;"></i> Explore Categories</h2>
                <a href="browse.php" class="view-all">All Categories <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="categories-grid">
                <?php foreach($categories as $category): ?>
                <a href="browse.php?category=<?= urlencode($category['category']) ?>" class="category-card">
                    <?php 
                    $icons = [
                        'Entertainment' => 'fas fa-film',
                        'Education' => 'fas fa-graduation-cap',
                        'Gaming' => 'fas fa-gamepad',
                        'Music' => 'fas fa-music',
                        'Sports' => 'fas fa-football-ball',
                        'Technology' => 'fas fa-laptop-code',
                        'Other' => 'fas fa-star'
                    ];
                    $icon = $icons[$category['category']] ?? 'fas fa-folder';
                    ?>
                    <div class="category-icon">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <div class="category-name"><?= htmlspecialchars($category['category']) ?></div>
                    <div class="category-count"><?= $category['count'] ?> videos</div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Top Creators -->
        <?php if(count($top_creators) > 0): ?>
        <section class="creators-section">
            <div class="section-header">
                <h2><i class="fas fa-crown" style="color: #ffd166; margin-right: 10px;"></i> Top Creators</h2>
                <a href="browse.php" class="view-all">View All Creators <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="creators-grid">
                <?php foreach($top_creators as $creator): ?>
                <div class="creator-card">
                    <img src="<?= getProfilePicUrl($creator['profile_pic']) ?>" 
                         alt="<?= htmlspecialchars($creator['username']) ?>"
                         class="creator-avatar"
                         onerror="this.src='assets/images/default-avatar.jpg'">
                    <div class="creator-name"><?= htmlspecialchars($creator['username']) ?></div>
                    <div class="creator-videos"><?= $creator['video_count'] ?> videos</div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA Section -->
        <section class="featured-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="featured-content">
                <h2 style="color: white;">Ready to Start Streaming?</h2>
                <p style="color: rgba(255, 255, 255, 0.9);">Join thousands of creators who are already sharing their stories on StreamHub. It's free to join and easy to get started.</p>
                
                <div class="hero-actions" style="margin-top: 40px;">
                    <?php if(!isLoggedIn()): ?>
                        <a href="register.php" class="btn-primary btn-large" style="background: white; color: #667eea;">
                            <i class="fas fa-user-plus"></i> Create Free Account
                        </a>
                        <a href="browse.php" class="btn-secondary" style="border-color: white; color: white;">
                            <i class="fas fa-play-circle"></i> Watch Demo
                        </a>
                    <?php else: ?>
                        <a href="upload.php" class="btn-primary btn-large" style="background: white; color: #667eea;">
                            <i class="fas fa-cloud-upload-alt"></i> Upload New Video
                        </a>
                        <a href="dashboard.php" class="btn-secondary" style="border-color: white; color: white;">
                            <i class="fas fa-chart-line"></i> View Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/app.js"></script>
    
    <script>
    // Add hover effects to video cards
    document.addEventListener('DOMContentLoaded', function() {
        // Add click tracking for video cards
        document.querySelectorAll('.video-card a').forEach(link => {
            link.addEventListener('click', function(e) {
                // You could add analytics tracking here
                console.log('Video clicked:', this.href);
            });
        });
        
        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.stat-card').forEach(card => {
            observer.observe(card);
        });
    });
    </script>
</body>
</html>