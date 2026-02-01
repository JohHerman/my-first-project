<?php
// fix_passwords.php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 Fixing Password Issue</h1>";

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'streaming_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to database<br>";

// Generate correct hash for 'password123'
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "✅ Generated hash for 'password123':<br>";
echo "<code>" . $hash . "</code><br><br>";

// List current users
echo "<h2>📋 Current Users:</h2>";
$result = $conn->query("SELECT id, email, username, password_hash FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Email</th><th>Username</th><th>Current Hash</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['username']}</td>";
    echo "<td>" . substr($row['password_hash'], 0, 30) . "...</td>";
    echo "</tr>";
}
echo "</table><br>";

// Update all passwords
echo "<h2>🔄 Updating Passwords...</h2>";
$update_result = $conn->query("UPDATE users SET password_hash = '$hash'");
if ($update_result) {
    echo "✅ Updated " . $conn->affected_rows . " user(s)<br>";
} else {
    echo "❌ Update failed: " . $conn->error . "<br>";
}

// Verify the update
echo "<h2>✅ Verification:</h2>";
$verify = $conn->query("SELECT email FROM users WHERE password_hash = '$hash'");
echo "Users with correct password: " . $verify->num_rows . "<br>";

// Test the hash
echo "<h2>🧪 Testing Password Verification:</h2>";
$test_hash = password_hash('password123', PASSWORD_BCRYPT);
echo "Test hash: " . substr($test_hash, 0, 30) . "...<br>";
echo "password_verify('password123', hash): " . 
     (password_verify('password123', $hash) ? '✅ TRUE' : '❌ FALSE') . "<br>";

$conn->close();

echo "<h2>🎯 Test Credentials:</h2>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@stream.com / password123</li>";
echo "<li><strong>User:</strong> user@stream.com / password123</li>";
echo "</ul>";

echo "<h2>📝 Test Command:</h2>";
echo "<pre>";
echo "curl -X POST \"http://localhost/streaming-platform/api/auth.php?action=login\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"email\":\"admin@stream.com\",\"password\":\"password123\"}'";
echo "</pre>";

echo "<p><a href='http://localhost/streaming-platform/public/'>🏠 Go to Homepage</a></p>";
?>