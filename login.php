<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include config first to setup autoload
require_once '../../includes/config.php';

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid JSON data received'
    ]);
    exit;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$remember_me = isset($input['remember_me']) && $input['remember_me'] === true;
$csrf_token = $input['csrf_token'] ?? '';

// Validate required fields
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Username and password are required'
    ]);
    exit;
}

try {
    $auth = new Auth();
    $result = $auth->login($username, $password, $remember_me);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Login exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>