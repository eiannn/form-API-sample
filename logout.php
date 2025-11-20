<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config first to setup autoload
require_once '../includes/config.php';

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || $_POST[CSRF_TOKEN_NAME] !== $_SESSION[CSRF_TOKEN_NAME]) {
        die('CSRF token validation failed');
    }
}

try {
    $auth = new Auth();

    // Log logout activity before destroying session
    if (isset($_SESSION['user_id'])) {
        $auth->logActivity($_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }

    $auth->logout();

    header('Location: index.php');
    exit;
    
} catch (Exception $e) {
    // If there's an error, still try to destroy session
    $_SESSION = [];
    session_destroy();
    
    header('Location: index.php');
    exit;
}
?>