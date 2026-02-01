<?php
session_start();
require_once 'config/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    
    if (empty($title) || empty($_FILES['video']['name'])) {
        $error = 'Title and video file are required';
    } else {
        // Handle video upload
        $video_result = handleFileUpload(
            $_FILES['video'], 
            VIDEO_PATH, 
            ALLOWED_VIDEO_TYPES, 
            MAX_VIDEO_SIZE
        );
        
        if (!$video_result['success']) {
            $error = $video_result['error'];
        } else {
            $video_filename = $video_result['filename'];
            $thumbnail_filename = 'default-thumbnail.jpg';
            
            // Handle thumbnail upload
            if (!empty($_FILES['thumbnail']['name'])) {
                $thumb_result = handleFileUpload(
                    $_FILES['thumbnail'], 
                    THUMB_PATH, 
                    ALLOWED_IMAGE_TYPES, 
                    5 * 1024 * 1024 // 5MB max for thumbnails
                );
                
                if ($thumb_result['success']) {
                    $thumbnail_filename = $thumb_result['filename'];
                }
            }
            
            // Get video duration (simplified - in real app use FFmpeg)
            $duration = 120; // Default 2 minutes
            
            // Save to database
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO videos (user_id, title, description, filename, thumbnail, category, duration)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $video_filename,
                $thumbnail_filename,
                $category,
                $duration
            ])) {
                $success = 'Video uploaded successfully!';
                $_POST = []; // Clear form
            } else {
                $error = 'Failed to save video to database';
                // Clean up uploaded files
                @unlink(VIDEO_PATH . $video_filename);
                if ($thumbnail_filename != 'default-thumbnail.jpg') {
                    @unlink(THUMB_PATH . $thumbnail_filename);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - StreamHub</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .upload-info {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .upload-info h4 {
            margin-top: 0;
            color: #0066cc;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
            display: none;
        }
        .progress {
            height: 100%;
            background: #007bff;
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <main class="container">
        <div class="upload-form-container">
            <h1><i class="fas fa-upload"></i> Upload Video</h1>
            
            <div class="upload-info">
                <h4><i class="fas fa-info-circle"></i> XAMPP Upload Information</h4>
                <p>Max file size: <?= ini_get('upload_max_filesize') ?></p>
                <p>Allowed video formats: MP4, WebM, AVI, MOV</p>
                <p>Store location: <?= VIDEO_PATH ?></p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <br><a href="dashboard.php">View your videos</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                <div class="form-group">
                    <label for="video"><i class="fas fa-video"></i> Video File *</label>
                    <input type="file" id="video" name="video" accept="video/*" required
                           onchange="checkFileSize(this)">
                    <small>Max: <?= ini_get('upload_max_filesize') ?>. Supported: MP4, WebM, AVI, MOV</small>
                    <div id="video-preview" style="margin-top: 10px;"></div>
                </div>
                
                <div class="progress-bar" id="uploadProgress">
                    <div class="progress" id="progressBar"></div>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail"><i class="fas fa-image"></i> Thumbnail (Optional)</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
                           onchange="previewImage(this)">
                    <small>Recommended: 1280x720 pixels</small>
                    <div id="thumbnail-preview" style="margin-top: 10px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="title"><i class="fas fa-heading"></i> Title *</label>
                    <input type="text" id="title" name="title" 
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                           placeholder="Enter video title" required>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Describe your video..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category"><i class="fas fa-tag"></i> Category</label>
                    <select id="category" name="category">
                        <option value="Entertainment">Entertainment</option>
                        <option value="Education">Education</option>
                        <option value="Gaming">Gaming</option>
                        <option value="Music">Music</option>
                        <option value="Sports">Sports</option>
                        <option value="Technology">Technology</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary btn-large" id="uploadBtn">
                    <i class="fas fa-upload"></i> Upload Video
                </button>
                <a href="dashboard.php" class="btn-action">Cancel</a>
            </form>
        </div>
    </main>

    <script src="assets/js/upload.js"></script>
    <script>
    function checkFileSize(input) {
        const file = input.files[0];
        const maxSize = 500 * 1024 * 1024; // 500MB
        
        if (file && file.size > maxSize) {
            alert('File is too large! Max size is 500MB.');
            input.value = '';
            document.getElementById('video-preview').innerHTML = '';
        } else if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('video-preview').innerHTML = 
                    `<p>Selected: ${file.name} (${(file.size / (1024*1024)).toFixed(2)} MB)</p>`;
            };
            reader.readAsDataURL(file);
        }
    }
    
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('thumbnail-preview').innerHTML = 
                    `<img src="${e.target.result}" style="max-width: 200px; border-radius: 4px;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Form submission with progress indicator
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('uploadBtn');
        const progress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        progress.style.display = 'block';
        
        // Simulate progress (in real app, use XMLHttpRequest with progress event)
        let width = 0;
        const interval = setInterval(() => {
            width += 10;
            progressBar.style.width = width + '%';
            
            if (width >= 90) {
                clearInterval(interval);
            }
        }, 500);
    });
    </script>
</body>
</html>