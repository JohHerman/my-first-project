<?php
echo "<h1>XAMPP Test Page</h1>";

// Test PHP
echo "PHP Version: " . phpversion() . "<br>";

// Test MySQL
try {
    $pdo = new PDO('mysql:host=localhost;dbname=streaming_platform', 'root', '');
    echo "MySQL: Connected successfully<br>";
} catch(PDOException $e) {
    echo "MySQL Error: " . $e->getMessage() . "<br>";
}

// Test file permissions
$dirs = ['uploads/', 'uploads/videos/', 'uploads/thumbnails/', 'uploads/profiles/'];
foreach($dirs as $dir) {
    if(is_writable($dir)) {
        echo "$dir: Writable ✓<br>";
    } else {
        echo "$dir: NOT Writable ✗<br>";
    }
}

// Test session
session_start();
$_SESSION['test'] = 'success';
echo "Session: " . ($_SESSION['test'] == 'success' ? 'Working ✓' : 'Not working ✗') . "<br>";

echo "<h3>PHP Info:</h3>";
echo "<a href='?phpinfo=1'>Show PHP Info</a>";
if(isset($_GET['phpinfo'])) phpinfo();
?>