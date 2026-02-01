<?php
// ============================================
// SECURITY FUNCTIONS
// Streaming Platform Backend System
// ============================================

// Sanitize input data
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function validate_password($password) {
    return strlen($password) >= 6;
}

// Generate CSRF token
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Set security headers
function set_security_headers() {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'");
}

// Set CORS headers for API
function set_cors_headers() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Rate limiting function (simple implementation)
function check_rate_limit($key, $max_requests = 100, $period = 60) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.tmp';
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if (time() - $data['timestamp'] > $period) {
            $data = ['count' => 0, 'timestamp' => time()];
        }
        $data['count']++;
    } else {
        $data = ['count' => 1, 'timestamp' => time()];
    }
    
    file_put_contents($cache_file, json_encode($data));
    
    return $data['count'] <= $max_requests;
}

// Input validation for video upload
function validate_video_upload($file) {
    $errors = [];
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = "File too large. Maximum size is " . (MAX_UPLOAD_SIZE / 1024 / 1024) . "MB";
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_VIDEO_TYPES)) {
        $errors[] = "Invalid file type. Allowed types: MP4, WebM, OGG";
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error: " . $file['error'];
    }
    
    return empty($errors) ? true : $errors;
}