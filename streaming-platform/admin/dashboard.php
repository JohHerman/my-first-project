<?php
session_start();
require_once '../config/functions.php';
requireAdmin(); // Only admins can access

$db = getDB();

// Get stats for admin dashboard
$total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$total_videos = $db->query("SELECT COUNT(*) as count FROM videos")->fetch()['count'];
$total_views = $db->query("SELECT SUM(views) as total FROM videos")->fetch()['total'] ?? 0;
$pending_reports = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch()['count'];
$new_users_today = $db->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
$new_videos_today = $db->query("SELECT COUNT(*) as count FROM videos WHERE DATE(created_at) = CURDATE()")->fetch()['count'];

// Get recent activities
$recent_videos = $db->query("
    SELECT v.*, u.username 
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT 5
")->fetchAll();

$recent_users = $db->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

$pending_reports_list = $db->query("
    SELECT r.*, v.title as video_title, u.username as reporter 
    FROM reports r 
    JOIN videos v ON r.video_id = v.id 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StreamHub</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: #1a1a2e;
            color: white;
            padding: 20px 0;
        }
        .admin-main {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
        }
        .admin-logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #2d3047;
        }
        .admin-nav {
            padding: 20px 0;
        }
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #b0b3c1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .admin-nav a:hover {
            background: #2d3047;
            color: white;
        }
        .admin-nav a.active {
            background: #2d3047;
            color: white;
            border-left-color: #ff0000;
        }
        .admin-nav a i {
            width: 20px;
            text-align: center;
        }
        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .admin-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .admin-stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .admin-stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ff0000;
        }
        .admin-stat-change {
            font-size: 0.8em;
            color: #28a745;
            margin-top: 5px;
        }
        .admin-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }
        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .btn-admin {
            background: #ff0000;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .btn-admin:hover {
            background: #cc0000;
        }
        .btn-admin-small {
            padding: 4px 8px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-user-shield"></i> Admin Panel</h2>
                <small>Logged in as: <?= $_SESSION['username'] ?></small>
            </div>
            
            <nav class="admin-nav">
                <a href="dashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php">
                    <i class="fas fa-users"></i> Users
                    <span class="badge" style="margin-left: auto;"><?= $total_users ?></span>
                </a>
                <a href="videos.php">
                    <i class="fas fa-video"></i> Videos
                    <span class="badge" style="margin-left: auto;"><?= $total_videos ?></span>
                </a>
                <a href="reports.php">
                    <i class="fas fa-flag"></i> Reports
                    <?php if($pending_reports > 0): ?>
                        <span class="badge badge-danger" style="margin-left: auto;"><?= $pending_reports ?></span>
                    <?php endif; ?>
                </a>
                <a href="analytics.php">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
                <a href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../dashboard.php">
                    <i class="fas fa-user"></i> User View
                </a>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <div>
                    <span class="badge badge-success">Admin</span>
                    <a href="../index.php" class="btn-admin">
                        <i class="fas fa-home"></i> Visit Site
                    </a>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <h3><i class="fas fa-users"></i> Total Users</h3>
                    <p class="admin-stat-number"><?= number_format($total_users) ?></p>
                    <p class="admin-stat-change">
                        <i class="fas fa-user-plus"></i> +<?= $new_users_today ?> today
                    </p>
                </div>
                
                <div class="admin-stat-card">
                    <h3><i class="fas fa-video"></i> Total Videos</h3>
                    <p class="admin-stat-number"><?= number_format($total_videos) ?></p>
                    <p class="admin-stat-change">
                        <i class="fas fa-video"></i> +<?= $new_videos_today ?> today
                    </p>
                </div>
                
                <div class="admin-stat-card">
                    <h3><i class="fas fa-eye"></i> Total Views</h3>
                    <p class="admin-stat-number"><?= number_format($total_views) ?></p>
                </div>
                
                <div class="admin-stat-card">
                    <h3><i class="fas fa-flag"></i> Pending Reports</h3>
                    <p class="admin-stat-number"><?= $pending_reports ?></p>
                    <?php if($pending_reports > 0): ?>
                        <p class="admin-stat-change" style="color: #ff0000;">
                            <i class="fas fa-exclamation-circle"></i> Needs attention
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Activities Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Recent Videos -->
                <div class="admin-card">
                    <h3><i class="fas fa-video"></i> Recent Videos</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>User</th>
                                <th>Views</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_videos as $video): ?>
                            <tr>
                                <td>
                                    <a href="../watch.php?id=<?= $video['id'] ?>" style="color: #333;">
                                        <?= substr($video['title'], 0, 30) . (strlen($video['title']) > 30 ? '...' : '') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($video['username']) ?></td>
                                <td><?= number_format($video['views']) ?></td>
                                <td><?= date('M d', strtotime($video['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; margin-top: 10px;">
                        <a href="videos.php" class="btn-admin">View All Videos</a>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="admin-card">
                    <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?>
                                    <?php if($user['is_admin']): ?>
                                        <span class="badge badge-danger">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php if($user['is_banned']): ?>
                                        <span class="badge badge-danger">Banned</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="manageUser(<?= $user['id'] ?>)" 
                                            class="btn-admin btn-admin-small">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; margin-top: 10px;">
                        <a href="users.php" class="btn-admin">View All Users</a>
                    </div>
                </div>
            </div>
            
            <!-- Pending Reports -->
            <?php if(count($pending_reports_list) > 0): ?>
            <div class="admin-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Pending Reports</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Video</th>
                            <th>Reporter</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_reports_list as $report): ?>
                        <tr>
                            <td>
                                <a href="../watch.php?id=<?= $report['video_id'] ?>">
                                    <?= substr($report['video_title'], 0, 30) . (strlen($report['video_title']) > 30 ? '...' : '') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($report['reporter']) ?></td>
                            <td><?= substr($report['reason'], 0, 50) . (strlen($report['reason']) > 50 ? '...' : '') ?></td>
                            <td><?= date('M d', strtotime($report['created_at'])) ?></td>
                            <td>
                                <button onclick="resolveReport(<?= $report['id'] ?>)" 
                                        class="btn-admin btn-admin-small" style="background: #28a745;">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="dismissReport(<?= $report['id'] ?>)" 
                                        class="btn-admin btn-admin-small" style="background: #6c757d;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 10px;">
                    <a href="reports.php" class="btn-admin">View All Reports</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function manageUser(userId) {
        window.location.href = `users.php?action=edit&id=${userId}`;
    }
    
    function resolveReport(reportId) {
        if (confirm('Mark this report as resolved?')) {
            fetch(`../api/admin.php?action=resolve_report&id=${reportId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        }
    }
    
    function dismissReport(reportId) {
        if (confirm('Dismiss this report?')) {
            fetch(`../api/admin.php?action=dismiss_report&id=${reportId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        }
    }
    
    // Auto refresh dashboard every 30 seconds
    setInterval(() => {
        fetch('dashboard.php?partial=1')
            .then(response => response.text())
            .then(html => {
                // Update specific sections if needed
            });
    }, 30000);
    </script>
</body>
</html>