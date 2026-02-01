<?php
require_once 'config/database.php';

try {
    $db = getDB();
    
    echo "<h2>Creating Admin User</h2>";
    
    // Check if admin already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "Admin user already exists!<br>";
        echo "Username: " . $admin['username'] . "<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Is Admin: " . ($admin['is_admin'] ? 'Yes' : 'No') . "<br>";
        
        // Make sure admin has admin privileges
        if (!$admin['is_admin']) {
            $db->query("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
            echo "✓ Updated admin privileges<br>";
        }
    } else {
        // Create admin user with password: admin123
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, is_admin, profile_pic, created_at) 
            VALUES (?, ?, ?, 1, 'default-avatar.jpg', NOW())
        ");
        
        if ($stmt->execute(['admin', 'admin@streamhub.com', $hashed_password])) {
            echo "✓ Admin user created successfully!<br>";
            echo "Username: <strong>admin</strong><br>";
            echo "Password: <strong>admin123</strong><br>";
            echo "Email: admin@streamhub.com<br>";
        } else {
            echo "Failed to create admin user<br>";
        }
    }
    
    // Also create a test regular user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'testuser'");
    $stmt->execute();
    $testuser = $stmt->fetch();
    
    if (!$testuser) {
        $hashed_password = password_hash('user123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, is_admin, profile_pic, created_at) 
            VALUES (?, ?, ?, 0, 'default-avatar.jpg', NOW())
        ");
        $stmt->execute(['testuser', 'user@streamhub.com', $hashed_password]);
        echo "✓ Test user created (username: testuser, password: user123)<br>";
    }
    
    echo "<hr><h3>Setup Complete!</h3>";
    echo "<strong>Admin Login:</strong> admin / admin123<br>";
    echo "<strong>User Login:</strong> testuser / user123<br>";
    echo "<a href='login.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px;'>Go to Login Page</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>