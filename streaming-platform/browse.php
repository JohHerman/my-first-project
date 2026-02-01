<?php
session_start();
require_once 'config/functions.php';

$db = getDB();

// Get categories with icons
$categories = [
    'All' => 'fas fa-globe',
    'Entertainment' => 'fas fa-film',
    'Education' => 'fas fa-graduation-cap',
    'Gaming' => 'fas fa-gamepad',
    'Music' => 'fas fa-music',
    'Sports' => 'fas fa-running',
    'Technology' => 'fas fa-microchip',
    'Other' => 'fas fa-ellipsis-h'
];

// Get videos based on filters
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($category && $category !== 'All') {
    $where .= " AND v.category = ?";
    $params[] = $category;
}

if ($search) {
    $where .= " AND (v.title LIKE ? OR v.description LIKE ? OR u.username LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Sort options - removing reference to non-existent 'likes' column
$order_by = match($sort) {
    'views' => 'v.views DESC',
    'oldest' => 'v.created_at ASC',
    'trending' => 'v.views DESC', // Simplified trending without likes
    default => 'v.created_at DESC'
};

// Simplified query without subqueries for likes and comments tables
$query = "SELECT v.*, u.username, u.profile_pic 
          FROM videos v 
          JOIN users u ON v.user_id = u.id 
          WHERE $where 
          ORDER BY $order_by";

$stmt = $db->prepare($query);
$stmt->execute($params);
$videos = $stmt->fetchAll();

// Count total videos for statistics (optional)
$countQuery = "SELECT COUNT(*) as total FROM videos v JOIN users u ON v.user_id = u.id WHERE $where";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalVideos = $countStmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Videos - StreamHub</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --accent: #ec4899;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #9ca3af;
        }
        
        .hero-banner {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 4rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" opacity="0.1"><path fill="white" d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z"/></svg>');
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-bar {
            display: flex;
            max-width: 500px;
            margin: 2rem auto 0;
            background: white;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .search-bar input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            outline: none;
            font-size: 1rem;
        }
        
        .search-bar button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0 2rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .search-bar button:hover {
            background: #db2777;
        }
        
        .category-tabs {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            justify-content: center;
        }
        
        .category-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 15px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s;
            border: 2px solid transparent;
            min-width: 100px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .category-tab:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .category-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .category-tab i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-sort {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .filter-sort select {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            font-weight: 500;
            cursor: pointer;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .filter-sort select:focus {
            border-color: var(--primary);
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .video-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .video-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .thumbnail {
            position: relative;
            aspect-ratio: 16/9;
            overflow: hidden;
        }
        
        .thumbnail img {
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
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        
        .video-info {
            padding: 1rem;
        }
        
        .video-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            line-height: 1.4;
            display: -webkit-box;
           
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .channel-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .channel-info img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .video-stats {
            display: flex;
            justify-content: space-between;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .video-stats span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--accent);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .stats-bar {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-around;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            padding: 0 1rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .category-tabs {
                gap: 0.5rem;
            }
            
            .category-tab {
                padding: 0.75rem 1rem;
                min-width: 80px;
            }
            
            .stats-bar {
                flex-wrap: wrap;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main class="container">
        <div class="hero-banner">
            <div class="hero-content">
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Discover Amazing Videos</h1>
                <p style="font-size: 1.1rem; opacity: 0.9;">Explore content from creators around the world</p>
                
                <form method="GET" action="browse.php" class="search-bar">
                    <input type="text" name="search" placeholder="Search videos, channels, or topics..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
        
        <?php if(!$search && !$category): ?>
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?= $totalVideos ?></div>
                <div class="stat-label">Videos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($categories) - 1 ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">Live</div>
                <div class="stat-label">24/7 Access</div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="category-tabs">
            <?php foreach($categories as $cat => $icon): ?>
                <a href="browse.php?category=<?= urlencode($cat) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                   class="category-tab <?= ($category ?: 'All') == $cat ? 'active' : '' ?>">
                    <i class="<?= $icon ?>"></i>
                    <span><?= htmlspecialchars($cat) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="filter-sort">
            <span style="font-weight: 600; color: var(--dark);">Sort by:</span>
            <select onchange="updateSort(this.value)">
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="trending" <?= $sort == 'trending' ? 'selected' : '' ?>>Trending</option>
                <option value="views" <?= $sort == 'views' ? 'selected' : '' ?>>Most Viewed</option>
                <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            </select>
            <?php if($search): ?>
                <span style="margin-left: auto; color: var(--gray);">
                    <?= count($videos) ?> results for "<?= htmlspecialchars($search) ?>"
                </span>
            <?php endif; ?>
        </div>
        
        <div class="video-grid">
            <?php if(count($videos) > 0): ?>
                <?php foreach($videos as $index => $video): ?>
                <div class="video-card">
                    <?php if($index < 3 && $sort == 'trending'): ?>
                        <div class="badge">Trending #<?= $index + 1 ?></div>
                    <?php endif; ?>
                    <a href="watch.php?id=<?= $video['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="thumbnail">
                            <img src="<?= getThumbnailUrl($video['thumbnail']) ?>" alt="<?= $video['title'] ?>">
                            <span class="duration"><?= formatDuration($video['duration']) ?></span>
                        </div>
                        <div class="video-info">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <div class="channel-info">
                                <img src="<?= getProfilePicUrl($video['profile_pic']) ?>" alt="<?= $video['username'] ?>">
                                <span style="font-weight: 500;"><?= htmlspecialchars($video['username']) ?></span>
                            </div>
                            <div class="video-stats">
                                <span><i class="fas fa-eye"></i> <?= number_format($video['views']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($video['created_at'])) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search fa-3x"></i>
                    <h3>No videos found</h3>
                    <p><?= $search ? 'Try a different search term' : 'Try browsing a different category' ?></p>
                    <?php if(!$search): ?>
                        <a href="upload.php" class="btn-primary" style="display: inline-block; margin-top: 1rem;">Upload Your First Video</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function updateSort(sortValue) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortValue);
            window.location.href = url.toString();
        }
        
        // Add hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const videoCards = document.querySelectorAll('.video-card');
            videoCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.zIndex = '1';
                });
            });
        });
    </script>
</body>
</html>