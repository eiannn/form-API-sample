<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config first to setup autoload
require_once '../includes/config.php';

try {
    $auth = new Auth();

    if (!$auth->isLoggedIn()) {
        header('Location: index.php');
        exit;
    }

    // Log dashboard access
    $auth->logActivity($_SESSION['user_id'], 'DASHBOARD_ACCESS', 'User accessed dashboard');

    // Get user info for display
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $email = $_SESSION['email'];
    $login_time = $_SESSION['login_time'];
    
    // Get user file data and stats
    $user_activities = $auth->getUserFileData($user_id, 5); // Last 5 activities
    $user_stats = $auth->getUserFileStats($user_id);
    
} catch (Exception $e) {
    // If there's an error, redirect to login
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Your existing CSS remains the same */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            margin-bottom: 20px;
            text-align: center;
            color: white;
        }

        .dashboard-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            color: white;
            margin-bottom: 20px;
        }

        .user-info {
            margin-bottom: 30px;
        }

        .user-info h3 {
            margin-bottom: 15px;
            font-weight: 300;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #51cf66;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }

        .activity-list {
            margin-top: 20px;
        }

        .activity-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .activity-action {
            font-weight: bold;
            color: #51cf66;
        }

        .activity-time {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 5px;
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s ease;
            margin-top: 20px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
        }

        .security-features {
            margin-top: 30px;
        }

        .feature-list {
            list-style: none;
        }

        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feature-list li:before {
            content: "✓ ";
            color: #51cf66;
            font-weight: bold;
        }

        .section-title {
            margin: 25px 0 15px 0;
            font-weight: 300;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome to Your Dashboard, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Secure Authentication System with File Storage</p>
        </div>

        <div class="dashboard-content">
            <div class="user-info">
                <h3>Account Information</h3>
                <div class="info-item">
                    <span>User ID:</span>
                    <span><?php echo $user_id; ?></span>
                </div>
                <div class="info-item">
                    <span>Username:</span>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
                <div class="info-item">
                    <span>Email:</span>
                    <span><?php echo htmlspecialchars($email); ?></span>
                </div>
                <div class="info-item">
                    <span>Login Time:</span>
                    <span><?php echo date('Y-m-d H:i:s', $login_time); ?></span>
                </div>
            </div>

            <!-- User Statistics -->
            <h3 class="section-title">User Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['total_activities']; ?></div>
                    <div class="stat-label">Total Activities</div>
                </div>
                <?php if (!empty($user_stats['activities_by_type'])): ?>
                    <?php foreach ($user_stats['activities_by_type'] as $action => $count): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $count; ?></div>
                        <div class="stat-label"><?php echo $action; ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <h3 class="section-title">Recent Activities</h3>
            <div class="activity-list">
                <?php if (!empty($user_activities)): ?>
                    <?php foreach ($user_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-action"><?php echo $activity['action']; ?></div>
                        <div><?php echo $activity['data']['description'] ?? 'No description'; ?></div>
                        <div class="activity-time">
                            <?php echo $activity['datetime']; ?> | 
                            IP: <?php echo $activity['ip_address']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        No activities recorded yet.
                    </div>
                <?php endif; ?>
            </div>

            <div class="security-features">
                <h3 class="section-title">Security Features Active</h3>
                <ul class="feature-list">
                    <li>Dual Database System</li>
                    <li>File-based Data Storage</li>
                    <li>Password Hashing (bcrypt)</li>
                    <li>CSRF Token Protection</li>
                    <li>Rate Limiting</li>
                    <li>SQL Injection Prevention</li>
                    <li>XSS Protection</li>
                    <li>Session Timeout</li>
                    <li>Secure Cookies</li>
                </ul>
            </div>

            <form action="logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[CSRF_TOKEN_NAME]; ?>">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>