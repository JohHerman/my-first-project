<?php
// For XAMPP on Windows
// define('SITE_URL', 'http://localhost/streaming-platform/');

// For XAMPP on Mac/Linux
// define('SITE_URL', 'http://localhost/streaming-platform/');

// For XAMPP default
define('SITE_URL', 'http://localhost/streaming-platform/');

// Or if using a different port:
// define('SITE_URL', 'http://localhost:8080/streaming-platform/');

// Adjust this path for XAMPP
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/streaming-platform/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('VIDEO_PATH', UPLOAD_PATH . 'videos/');
define('THUMB_PATH', UPLOAD_PATH . 'thumbnails/');
define('PROFILE_PATH', UPLOAD_PATH . 'profiles/');

define('MAX_VIDEO_SIZE', 500 * 1024 * 1024); // 500MB
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'avi', 'mov', 'm4v']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// For file uploads in XAMPP
ini_set('upload_max_filesize', '500M');
ini_set('post_max_size', '500M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
?>