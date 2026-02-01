<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="admin-logo">
        <h2><i class="fas fa-user-shield"></i> Admin Panel</h2>
        <small>Logged in as: <?= $_SESSION['username'] ?></small>
    </div>
    
    <nav class="admin-nav">
        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Users
        </a>
        <a href="videos.php" class="<?= $current_page == 'videos.php' ? 'active' : '' ?>">
            <i class="fas fa-video"></i> Videos
        </a>
        <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-flag"></i> Reports
        </a>
        <a href="analytics.php" class="<?= $current_page == 'analytics.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Analytics
        </a>
        <a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">
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