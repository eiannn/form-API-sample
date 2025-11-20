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
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';
$csrf_token = $input['csrf_token'] ?? '';

// Validate required fields
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'All fields are required'
    ]);
    exit;
}

// Check password confirmation
if ($password !== $confirm_password) {
    echo json_encode([
        'success' => false, 
        'message' => 'Passwords do not match'
    ]);
    exit;
}

try {
    $auth = new Auth();
    $result = $auth->register($username, $email, $password);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Signup exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>