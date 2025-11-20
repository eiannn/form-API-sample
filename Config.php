<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

// Primary Database
define('DB_NAME_PRIMARY', 'user_management');

// Secondary Database  
define('DB_NAME_SECONDARY', 'user_activity');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes
define('SESSION_TIMEOUT', 3600); // 1 hour

// Application Configuration
define('BASE_URL', 'http://localhost/security-API');
define('ROOT_PATH', dirname(dirname(__FILE__)));

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Generate CSRF Token
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $file = ROOT_PATH . '/includes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>