<?php
class Auth {
    private $db;
    private $primary_conn;
    private $secondary_conn;
    private $fileStorage;

    public function __construct() {
        // Check if Database class exists
        if (!class_exists('Database')) {
            throw new Exception('Database class not found. Please check includes path.');
        }
        
        $this->db = new Database();
        
        // Initialize FileStorage only if class exists
        if (class_exists('FileStorage')) {
            $this->fileStorage = new FileStorage();
        } else {
            error_log("FileStorage class not found - continuing without file logging");
            $this->fileStorage = null;
        }
        
        $this->primary_conn = $this->db->getPrimaryConnection();
        if (!$this->primary_conn) {
            throw new Exception('Primary database connection failed');
        }
        
        $this->secondary_conn = $this->db->getSecondaryConnection();
        if (!$this->secondary_conn) {
            throw new Exception('Secondary database connection failed');
        }
    }

    public function register($username, $email, $password) {
        // Input validation
        $username = $this->db->sanitizeInput($username);
        $email = $this->db->sanitizeInput($email);
        
        if (!$this->validateUsername($username) || !$this->validateEmail($email) || !$this->validatePassword($password)) {
            return ['success' => false, 'message' => 'Invalid input data'];
        }

        // Check if user already exists
        if ($this->userExists($username, $email)) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert user into primary database
            $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $this->db->prepareStatement($this->primary_conn, $sql, [$username, $email, $password_hash], "sss");
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                
                // Log registration activity in database
                $this->logActivity($user_id, 'REGISTRATION', 'User registered successfully');
                
                // Store in file system if available
                if ($this->fileStorage) {
                    $this->fileStorage->storeUserData($user_id, $username, 'REGISTRATION', [
                        'email' => $email,
                        'registration_method' => 'web_form'
                    ]);
                }
                
                return [
                    'success' => true, 
                    'message' => 'Registration successful',
                    'data' => ['user_id' => $user_id]
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during registration'];
        }
    }

    public function login($username, $password, $remember_me = false) {
        $username = $this->db->sanitizeInput($username);
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Check rate limiting
        if ($this->isRateLimited($username, $ip_address)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }

        try {
            // Get user from primary database
            $sql = "SELECT id, username, email, password_hash FROM users WHERE username = ? AND is_active = TRUE";
            $stmt = $this->db->prepareStatement($this->primary_conn, $sql, [$username], "s");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    // Successful login
                    $this->logLoginAttempt($username, $ip_address, true);
                    
                    // Create session
                    $this->createUserSession($user, $remember_me);
                    
                    // Log activity in database
                    $this->logActivity($user['id'], 'LOGIN', 'User logged in successfully');
                    
                    // Store in file system if available
                    if ($this->fileStorage) {
                        $this->fileStorage->storeUserData($user['id'], $username, 'LOGIN', [
                            'remember_me' => $remember_me,
                            'ip_address' => $ip_address,
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                    }
                    
                    return [
                        'success' => true, 
                        'message' => 'Login successful',
                        'data' => ['user_id' => $user['id'], 'username' => $user['username']]
                    ];
                }
            }
            
            // Failed login
            $this->logLoginAttempt($username, $ip_address, false);
            return ['success' => false, 'message' => 'Invalid username or password'];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login'];
        }
    }

    public function logActivity($user_id, $action, $description) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Store in database
        $sql = "INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepareStatement($this->secondary_conn, $sql, [$user_id, $action, $description, $ip_address], "isss");
        $stmt->execute();
        
        // Also store in file system if available
        if ($this->fileStorage) {
            $username = $this->getUsernameById($user_id);
            if ($username) {
                $this->fileStorage->storeUserData($user_id, $username, $action, [
                    'description' => $description,
                    'ip_address' => $ip_address
                ]);
            }
        }
    }

    private function getUsernameById($user_id) {
        $sql = "SELECT username FROM users WHERE id = ?";
        $stmt = $this->db->prepareStatement($this->primary_conn, $sql, [$user_id], "i");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            return $user['username'];
        }
        return null;
    }

    // Add method to get user file data
    public function getUserFileData($user_id, $limit = 10) {
        if ($this->fileStorage) {
            return $this->fileStorage->getUserActivity($user_id, $limit);
        }
        return [];
    }
    
    // Add method to get user file stats
    public function getUserFileStats($user_id) {
        if ($this->fileStorage) {
            return $this->fileStorage->getUserStats($user_id);
        }
        return [
            'total_activities' => 0,
            'first_activity' => null,
            'last_activity' => null,
            'activities_by_type' => []
        ];
    }

    // ... rest of your existing methods remain exactly the same ...
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->prepareStatement($this->primary_conn, $sql, [$username, $email], "ss");
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function isRateLimited($username, $ip_address) {
        $time_limit = date('Y-m-d H:i:s', time() - LOGIN_TIMEOUT);
        
        $sql = "SELECT COUNT(*) as attempt_count 
                FROM login_attempts 
                WHERE (username = ? OR ip_address = ?) 
                AND attempt_time > ? 
                AND success = FALSE";
        
        $stmt = $this->db->prepareStatement($this->secondary_conn, $sql, [$username, $ip_address, $time_limit], "sss");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['attempt_count'] >= MAX_LOGIN_ATTEMPTS;
    }

    private function logLoginAttempt($username, $ip_address, $success) {
        $sql = "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)";
        $stmt = $this->db->prepareStatement($this->secondary_conn, $sql, [$username, $ip_address, $success], "ssi");
        $stmt->execute();
    }

    private function createUserSession($user, $remember_me) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Store session in secondary database
        $session_id = session_id();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $sql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepareStatement($this->secondary_conn, $sql, [$user['id'], $session_id, $ip_address, $user_agent], "isss");
        $stmt->execute();

        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/', '', isset($_SERVER['HTTPS']), true);
        }
    }

    private function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }

    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validatePassword($password) {
        return strlen($password) >= 8;
    }

    public function isLoggedIn() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Check session timeout
            if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
            
            // Update last activity
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Log logout activity
            $this->logActivity($_SESSION['user_id'], 'LOGOUT', 'User logged out');
            
            // Store in file system if available
            if ($this->fileStorage) {
                $this->fileStorage->storeUserData($_SESSION['user_id'], $_SESSION['username'], 'LOGOUT', [
                    'session_duration' => time() - $_SESSION['login_time']
                ]);
            }
            
            // Mark session as inactive in database
            $sql = "UPDATE user_sessions SET is_active = FALSE WHERE session_id = ?";
            $stmt = $this->db->prepareStatement($this->secondary_conn, $sql, [session_id()], "s");
            $stmt->execute();
        }

        // Clear session data
        $_SESSION = [];
        session_destroy();
        
        // Clear remember me cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>