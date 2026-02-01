<?php
// ============================================
// STREAMING PLATFORM - PUBLIC INTERFACE
// Streaming Platform Backend System
// ============================================

// Load configuration
require_once __DIR__ . '/../config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Streaming Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header .version {
            color: #ecf0f1;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        
        .api-endpoints {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .endpoint-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #3498db;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .endpoint-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .endpoint-method {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .method-get {
            background: #61affe;
            color: white;
        }
        
        .method-post {
            background: #49cc90;
            color: white;
        }
        
        .endpoint-url {
            font-family: 'Courier New', monospace;
            background: #2c3e50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .endpoint-description {
            color: #666;
            line-height: 1.6;
        }
        
        .test-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219653;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .database-info {
            background: #e8f4fc;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .database-info h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-success {
            background: #27ae60;
            color: white;
        }
        
        .status-error {
            background: #e74c3c;
            color: white;
        }
        
        .credentials {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #ffc107;
        }
        
        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 <?php echo APP_NAME; ?></h1>
            <div class="version">Backend System v<?php echo APP_VERSION; ?></div>
            <p>Secure and Scalable Streaming Platform API</p>
        </div>
        
        <div class="content">
            <!-- Database Status -->
            <div class="section">
                <h2>📊 System Status</h2>
                <div class="database-info">
                    <?php
                    try {
                        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                        if ($db->connect_error) {
                            throw new Exception($db->connect_error);
                        }
                        echo '<h3>✅ Database Connection: <span class="status status-success">CONNECTED</span></h3>';
                        echo '<p>Database: <strong>streaming_db</strong></p>';
                        
                        // Get counts
                        $tables = ['users', 'videos', 'subscriptions', 'watch_history'];
                        foreach ($tables as $table) {
                            $result = $db->query("SELECT COUNT(*) as count FROM $table");
                            $row = $result->fetch_assoc();
                            echo "<p>{$table}: <strong>{$row['count']}</strong> records</p>";
                        }
                        
                        $db->close();
                    } catch (Exception $e) {
                        echo '<h3>❌ Database Connection: <span class="status status-error">FAILED</span></h3>';
                        echo '<p>Error: ' . $e->getMessage() . '</p>';
                        echo '<p>Make sure XAMPP MySQL is running!</p>';
                    }
                    ?>
                    
                    <div class="credentials">
                        <h4>🔐 Test Credentials:</h4>
                        <p><strong>Admin:</strong> admin@stream.com / admin123</p>
                        <p><strong>User:</strong> user@stream.com / user123</p>
                    </div>
                </div>
            </div>
            
            <!-- API Documentation -->
            <div class="section">
                <h2>📚 API Documentation</h2>
                <div class="api-endpoints">
                    <!-- Authentication -->
                    <div class="endpoint-card">
                        <div class="endpoint-method method-post">POST</div>
                        <div class="endpoint-url">/api/auth.php?action=register</div>
                        <div class="endpoint-description">
                            <strong>Register new user</strong><br>
                            JSON body: {email, password, username, full_name}
                        </div>
                    </div>
                    
                    <div class="endpoint-card">
                        <div class="endpoint-method method-post">POST</div>
                        <div class="endpoint-url">/api/auth.php?action=login</div>
                        <div class="endpoint-description">
                            <strong>User login</strong><br>
                            JSON body: {email, password}<br>
                            Returns JWT token
                        </div>
                    </div>
                    
                    <!-- Videos -->
                    <div class="endpoint-card">
                        <div class="endpoint-method method-get">GET</div>
                        <div class="endpoint-url">/api/videos.php?action=list</div>
                        <div class="endpoint-description">
                            <strong>List all videos</strong><br>
                            Optional: ?page=1&limit=20<br>
                            Returns paginated results
                        </div>
                    </div>
                    
                    <div class="endpoint-card">
                        <div class="endpoint-method method-get">GET</div>
                        <div class="endpoint-url">/api/videos.php?action=get&id=1</div>
                        <div class="endpoint-description">
                            <strong>Get video details</strong><br>
                            Returns video metadata and streaming URL
                        </div>
                    </div>
                    
                    <div class="endpoint-card">
                        <div class="endpoint-method method-post">POST</div>
                        <div class="endpoint-url">/api/videos.php?action=upload</div>
                        <div class="endpoint-description">
                            <strong>Upload video</strong><br>
                            Form-data: video, title, description<br>
                            Header: Authorization: Bearer {token}
                        </div>
                    </div>
                    
                    <!-- Streaming -->
                    <div class="endpoint-card">
                        <div class="endpoint-method method-get">GET</div>
                        <div class="endpoint-url">/api/stream.php?id=1</div>
                        <div class="endpoint-description">
                            <strong>Stream video</strong><br>
                            Direct video streaming with range support<br>
                            Supports seeking and partial content
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Test -->
            <div class="section">
                <h2>🚀 Quick Test</h2>
                <div class="test-buttons">
                    <a href="<?php echo APP_URL; ?>/api/auth.php?action=login" class="btn btn-primary" target="_blank">
                        🔑 Test Login API
                    </a>
                    <a href="<?php echo APP_URL; ?>/api/videos.php?action=list" class="btn btn-success" target="_blank">
                        🎬 List Videos
                    </a>
                    <a href="<?php echo APP_URL; ?>/api/stream.php?id=1" class="btn btn-warning" target="_blank">
                        📹 Stream Sample Video
                    </a>
                    <a href="<?php echo APP_URL; ?>/test_api.php" class="btn btn-primary" target="_blank">
                        🧪 Run Full Test
                    </a>
                </div>
                
                <p style="margin-top: 20px; color: #666;">
                    <strong>💡 Tip:</strong> Use <a href="https://www.postman.com/" target="_blank">Postman</a> or 
                    <a href="https://curl.se/" target="_blank">cURL</a> for API testing. For video upload, 
                    you'll need to use Postman with form-data.
                </p>
            </div>
            
            <!-- Assignment Info -->
            <div class="section">
                <h2>📋 Assignment Information</h2>
                <div style="background: #e8f4fc; padding: 20px; border-radius: 10px;">
                    <p><strong>Project:</strong> Backend System for Streaming Platform</p>
                    <p><strong>Technologies:</strong> PHP, MySQL, REST API, JWT Authentication</p>
                    <p><strong>Features:</strong></p>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>✅ User Registration & Authentication (JWT)</li>
                        <li>✅ Video Upload & Management</li>
                        <li>✅ Video Streaming with Range Support</li>
                        <li>✅ Database with Relations (MySQL)</li>
                        <li>✅ API Documentation</li>
                        <li>✅ Security Headers & CORS</li>
                        <li>✅ Error Handling & Validation</li>
                        <li>✅ Sample Data & Testing Interface</li>
                    </ul>
                    <p style="margin-top: 15px;">
                        <strong>Submission Deadline:</strong> January 13, 12:00 AM
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh database status every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
        // Copy API endpoint on click
        document.querySelectorAll('.endpoint-url').forEach(function(element) {
            element.addEventListener('click', function() {
                const text = this.textContent;
                navigator.clipboard.writeText(text).then(function() {
                    const original = element.textContent;
                    element.textContent = '✓ Copied!';
                    setTimeout(function() {
                        element.textContent = original;
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>