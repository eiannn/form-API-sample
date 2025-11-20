<?php
require_once '../includes/config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Authentication</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-box">
                <!-- Login Form -->
                <form id="loginForm" class="form active">
                    <h2>Welcome Back</h2>
                    
                    <div class="error-message"></div>
                    <div class="success-message"></div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[CSRF_TOKEN_NAME]; ?>">
                    
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-password">Show</button>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <label for="remember_me">Remember Me</label>
                    </div>
                    
                    <button type="submit" class="btn">Login</button>
                    
                    <div class="switch-form">
                        Don't have an account? <a href="#" data-form="signup">Sign Up</a>
                    </div>
                </form>

                <!-- Signup Form -->
                <form id="signupForm" class="form">
                    <h2>Create Account</h2>
                    
                    <div class="error-message"></div>
                    <div class="success-message"></div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[CSRF_TOKEN_NAME]; ?>">
                    
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-password">Show</button>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        <button type="button" class="toggle-password">Show</button>
                    </div>
                    
                    <button type="submit" class="btn">Sign Up</button>
                    
                    <div class="switch-form">
                        Already have an account? <a href="#" data-form="login">Login</a>
                    </div>
                </form>
            </div>
            
            <div class="slider-controls">
                <div class="slider-dot active" data-form="login"></div>
                <div class="slider-dot" data-form="signup"></div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>