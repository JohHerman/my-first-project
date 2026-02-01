-- ============================================
-- STREAMING PLATFORM DATABASE
-- For Backend System Development Assignment
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS streaming_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE streaming_db;

-- ========== USERS TABLE ==========
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== VIDEOS TABLE ==========
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    file_size BIGINT,
    duration INT,
    thumbnail VARCHAR(255),
    user_id INT NOT NULL,
    views INT DEFAULT 0,
    status ENUM('uploading', 'processing', 'ready', 'failed') DEFAULT 'uploading',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== SUBSCRIPTIONS TABLE ==========
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    plan ENUM('free', 'premium', 'pro') DEFAULT 'free',
    status ENUM('active', 'canceled', 'expired') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== WATCH HISTORY TABLE ==========
CREATE TABLE IF NOT EXISTS watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress_seconds INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    INDEX idx_user_video (user_id, video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== SAMPLE DATA ==========
-- Insert admin user (password: admin123)
INSERT INTO users (email, username, password_hash, full_name, role) VALUES
('admin@stream.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
('user@stream.com', 'testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'user');

-- Insert subscriptions
INSERT INTO subscriptions (user_id, plan, expires_at) VALUES
(1, 'pro', DATE_ADD(NOW(), INTERVAL 365 DAY)),
(2, 'free', DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Insert sample videos
INSERT INTO videos (title, description, filename, user_id, status, duration, views) VALUES
('Introduction to Streaming Platform', 'Welcome to our streaming service tutorial', 'intro.mp4', 1, 'ready', 120, 150),
('How to Upload Videos', 'Step-by-step guide for content creators', 'upload_guide.mp4', 1, 'ready', 180, 89),
('Best of Action Movies 2024', 'Collection of action movie trailers', 'action_trailers.mp4', 2, 'ready', 300, 245),
('Documentary: Nature Wonders', 'Explore beautiful nature scenes', 'nature_doc.mp4', 2, 'ready', 420, 112);

-- Insert watch history
INSERT INTO watch_history (user_id, video_id, progress_seconds, completed) VALUES
(2, 1, 120, TRUE),
(2, 2, 150, FALSE),
(2, 3, 300, TRUE);

-- ========== VIEW FOR ANALYTICS ==========
CREATE VIEW video_analytics AS
SELECT 
    v.id,
    v.title,
    v.views,
    COUNT(DISTINCT wh.user_id) as unique_viewers,
    AVG(wh.progress_seconds) as avg_watch_time,
    v.created_at
FROM videos v
LEFT JOIN watch_history wh ON v.id = wh.video_id
GROUP BY v.id;

-- ========== STORED PROCEDURE ==========
DELIMITER //
CREATE PROCEDURE GetUserVideos(IN user_id INT)
BEGIN
    SELECT v.*, u.username as uploader
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.user_id = user_id
    AND v.status = 'ready'
    ORDER BY v.created_at DESC;
END//
DELIMITER ;

-- Show tables
SHOW TABLES;