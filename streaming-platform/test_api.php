<?php
// Simple API test
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle login test
    $email = $_POST['email'] ?? 'admin@stream.com';
    $password = $_POST['password'] ?? 'password123';
    
    $data = [
        'email' => $email,
        'password' => $password
    ];
    
    $ch = curl_init(APP_URL . '/api/auth.php?action=login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    header('Content-Type: application/json');
    echo $response;
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
</head>
<body>
    <h1>API Test Form</h1>
    <form method="POST">
        <label>Email: <input type="email" name="email" value="admin@stream.com"></label><br>
        <label>Password: <input type="password" name="password" value="password123"></label><br>
        <button type="submit">Test Login</button>
    </form>
    
    <h3>Test Endpoints:</h3>
    <ul>
        <li><a href="<?php echo APP_URL; ?>/api/auth.php?action=login" target="_blank">Login (GET info)</a></li>
        <li><a href="<?php echo APP_URL; ?>/api/videos.php?action=list" target="_blank">List Videos</a></li>
        <li><a href="<?php echo APP_URL; ?>/api/videos.php?action=popular" target="_blank">Popular Videos</a></li>
        <li><a href="<?php echo APP_URL; ?>/api/stream.php?id=1" target="_blank">Stream Video 1</a></li>
    </ul>
    
    <h3>Test with cURL:</h3>
    <pre>
curl -X POST "<?php echo APP_URL; ?>/api/auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@stream.com","password":"password123"}'
    </pre>
</body>
</html>