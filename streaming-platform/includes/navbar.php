<?php
$current_user = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$avatar = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default-avatar.jpg';
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">
            <i class="fas fa-play-circle"></i> StreamHub
        </a>
        
        <div class="search-bar">
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Search videos..." value="<?= $_GET['q'] ?? '' ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="browse.php" class="nav-link">
                <i class="fas fa-compass"></i> Browse
            </a>
            
            <?php if(isLoggedIn()): ?>
                <a href="upload.php" class="nav-link upload-btn">
                    <i class="fas fa-upload"></i> Upload
                </a>
                
                <div class="user-dropdown">
                    <div class="user-menu-trigger">
                        <img src="<?= getProfilePicUrl($avatar) ?>" class="user-avatar" alt="Avatar">
                        <span class="username"><?= htmlspecialchars($current_user) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-user-circle"></i> My Dashboard
                        </a>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i> My Profile
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        
                        <?php if($is_admin): ?>
                            <div class="dropdown-divider"></div>
                            <a href="admin/dashboard.php" class="dropdown-item admin-link">
                                <i class="fas fa-user-shield"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                        
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="btn-primary">
                    Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.user-dropdown {
    position: relative;
}

.user-menu-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px;
    border-radius: 20px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.user-menu-trigger:hover {
    background-color: rgba(0,0,0,0.05);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.username {
    font-weight: 500;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 200px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 8px 0;
    z-index: 1000;
}

.user-dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-divider {
    height: 1px;
    background-color: #e9ecef;
    margin: 8px 0;
}

.admin-link {
    color: #dc3545;
}

.logout-link {
    color: #dc3545;
}

.upload-btn {
    background-color: #ff0000;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
}

.upload-btn:hover {
    background-color: #cc0000;
}

.nav-link {
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.nav-link:hover {
    background-color: rgba(0,0,0,0.05);
}
</style>