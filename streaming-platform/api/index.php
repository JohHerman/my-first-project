<?php
header('Content-Type: application/json');
require_once '../config/functions.php';

// Get endpoint from URL
$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Route the request
switch($endpoint) {
    case 'videos':
        require 'videos.php';
        break;
    case 'comments':
        require 'comments.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}
?>