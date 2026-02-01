<?php
session_start();
require_once 'config/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill all fields';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $db = getDB();
        
        // Check if username or email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            // Handle profile picture upload
            $profile_pic = 'default-avatar.jpg';
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
                $file = $_FILES['profile_pic'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($ext, $allowed)) {
                    $profile_pic = uniqid() . '.' . $ext;
                    move_uploaded_file($file['tmp_name'], PROFILE_PATH . $profile_pic);
                }
            }
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, profile_pic)
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$username, $email, $hashed_password, $profile_pic])) {
                // Auto-login after registration
                $user_id = $db->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['profile_pic'] = $profile_pic;
                $_SESSION['is_admin'] = false;
                
                redirect('dashboard.php');
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Register - StreamHub</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(50,50,93,.1), 0 5px 15px rgba(0,0,0,.07);
        }
        
        .auth-card h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-icon {
            position: relative;
        }
        
        .form-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .form-icon input {
            padding-left: 45px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .auth-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: block;
            border: 3px solid #eee;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            width: 100%;
        }
        
        .file-input-label:hover {
            border-color: #667eea;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1>
                <i class="fas fa-user-plus" style="color: #667eea; margin-right: 10px;"></i>
                Create Account
            </h1>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="auth-form">
                <div class="form-group">
                    <label for="profile_pic">Profile Picture (Optional)</label>
                    <label for="profile_pic" class="file-input-label">
                        <i class="fas fa-camera"></i> Choose Profile Picture
                    </label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
                    <img id="avatarPreview" class="avatar-preview" src="assets/images/default-avatar.jpg" alt="Preview">
                </div>
                
                <div class="form-group form-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" 
                           placeholder="Choose a username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                           required>
                </div>
                
                <div class="form-group form-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" 
                           placeholder="Your email address" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                           required>
                </div>
                
                <div class="form-group form-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" 
                           placeholder="Create a password (min. 6 characters)" 
                           required>
                </div>
                
                <div class="form-group form-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm password" 
                           required>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <p class="auth-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </p>
            
            <div class="login-footer">
                <p>By registering, you agree to our Terms of Service and Privacy Policy.</p>
            </div>
        </div>
    </div>
    
    <script>
    // Profile picture preview
    document.getElementById('profile_pic').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Click on label to trigger file input
    document.querySelector('.file-input-label').addEventListener('click', function() {
        document.getElementById('profile_pic').click();
    });
    
    // Form validation
    document.querySelector('.auth-form').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (!username || !email || !password || !confirmPassword) {
            e.preventDefault();
            alert('Please fill in all fields');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
            return false;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match');
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>