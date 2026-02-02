<?php
session_start();
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../includes/security.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

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
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
            $error = "All fields are required";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } else {
            // Check if username exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $hashed_password, $full_name, $email])) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
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
    <title>Register - Fund Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="register-container">
        <div class="auth-header">
            <div class="auth-logo">💰</div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join Fund Management System</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success-box"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <?php echo CSRF::field(); ?>
            
            <div>
                <input type="text" name="full_name" required placeholder="Full Name" 
                       class="input-underline">
            </div>
            
            <div>
                <input type="email" name="email" required placeholder="Email Address" 
                       class="input-underline">
            </div>
            
            <div>
                <input type="text" name="username" required placeholder="Username" 
                       class="input-underline">
            </div>
            
            <div>
                <input type="password" name="password" required placeholder="Password (min. 6 characters)"
                       class="input-underline">
            </div>
            
            <div>
                <input type="password" name="confirm_password" required placeholder="Confirm Password"
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
            
            <button type="submit" class="auth-button register">Create Account</button>
        </form>
        
        <div class="auth-divider">
            <p>Already have an account? <a href="login.php" class="auth-link">Login here</a></p>
        </div>
    </div>
</body>
</html>