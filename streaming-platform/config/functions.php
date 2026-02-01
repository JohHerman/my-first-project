<?php
require_once 'database.php';
require_once 'constants.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . SITE_URL . $url);
        exit();
    } else {
        echo "<script>window.location.href = '" . SITE_URL . $url . "';</script>";
        exit();
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// XAMPP path handling
function getVideoUrl($filename) {
    return 'uploads/videos/' . $filename;
}

function getThumbnailUrl($filename) {
    return 'uploads/thumbnails/' . $filename;
}

function getProfilePicUrl($filename) {
    return 'uploads/profiles/' . $filename;
}

function formatDuration($seconds) {
    if (!$seconds) return '0:00';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    }
    return sprintf("%d:%02d", $minutes, $seconds);
}

function getRelativeTime($timestamp) {
    $time = strtotime($timestamp);
    if (!$time) return 'Recently';
    
    $time_diff = time() - $time;
    
    if ($time_diff < 60) return "Just now";
    if ($time_diff < 3600) return floor($time_diff/60) . " minutes ago";
    if ($time_diff < 86400) return floor($time_diff/3600) . " hours ago";
    if ($time_diff < 604800) return floor($time_diff/86400) . " days ago";
    return date("M d, Y", $time);
}

// Admin check middleware
function requireAdmin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (!isAdmin()) {
        redirect('dashboard.php');
    }
}

// User check middleware
function requireUser() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (isAdmin()) {
        // Allow admins to access user dashboard too
        // Or redirect to admin dashboard
        // redirect('admin/dashboard.php');
    }
}

// Check if user is banned
function isBanned() {
    if (!isLoggedIn()) return false;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user && $user['is_banned'];
}
?>