<?php
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    }
    // CAPTCHA Validation
    elseif (!SimpleCaptcha::validate($_POST['captcha'] ?? '')) {
        $error = "Incorrect answer to math question.";
    }
    // Rate Limiting
    elseif (!RateLimiter::check('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
        $remaining = RateLimiter::getRemainingTime('login_' . $_SERVER['REMOTE_ADDR']);
        $error = "Too many login attempts. Please try again in " . ceil($remaining / 60) . " minutes.";
    }
    else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $db->prepare("SELECT id, username, password, full_name, email FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            
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

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$captcha_question = SimpleCaptcha::generate();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Suwan's Fund Manager</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container px-4">
        <div class="text-center mb-10">
            <div class="text-5xl mb-4">💰</div>
            <h1 class="text-3xl font-light text-gray-800">Suwan's Fund Manager</h1>
            <p class="text-gray-600 mt-2">Sign in to continue</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-box">
            <?php echo escape($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-8">
            <?php echo CSRF::getTokenField(); ?>
            
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
                <label class="block text-sm text-gray-600 mb-2">
                    Security Check: What is <?php echo escape($captcha_question); ?>?
                </label>
                <input type="number" name="captcha" required placeholder="Enter answer" 
                       class="input-underline">
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium text-lg transition duration-200">
                Sign In
            </button>
        </form>
        
        <div class="text-center mt-8 pt-6 border-t border-gray-200">
            <p class="text-gray-600">
                Don't have an account? 
                <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Register here
                </a>
            </p>
        </div>
    </div>
</body>
</html>
