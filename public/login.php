<?php
session_start();
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/security.php';

$database = new Database();
$db = $database->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Check CSRF token
    if (!CSRF::check($_POST['token'] ?? '')) {
        $error = "Invalid security token";
    }
    // Check CAPTCHA
    elseif (!CAPTCHA::check($_POST['captcha'] ?? '')) {
        $error = "Wrong answer to math question";
    }
    else {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Generate CAPTCHA question
$captcha_question = CAPTCHA::create();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fund Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="auth-header">
            <div class="auth-logo">💰</div>
            <h1 class="auth-title">Fund Management System</h1>
            <p class="auth-subtitle">Sign in to continue</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <?php echo CSRF::field(); ?>
            
            <div>
                <input type="text" name="username" required placeholder="Username" 
                       class="input-underline" autofocus>
            </div>
            
            <div>
                <input type="password" name="password" required placeholder="Password" 
                       class="input-underline">
            </div>
            
            <!-- CAPTCHA -->
            <div>
                <label style="display: block; font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                    Security: What is <?php echo $captcha_question; ?>?
                </label>
                <input type="number" name="captcha" required placeholder="Enter answer" 
                       class="input-underline">
            </div>
            
            <button type="submit" class="auth-button">Sign In</button>
        </form>
        
        <div class="auth-divider">
            <p>Don't have an account? <a href="register.php" class="auth-link">Register here</a></p>
        </div>
    </div>
</body>
</html>