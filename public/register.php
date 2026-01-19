<?php
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    }
    // CAPTCHA Validation
    elseif (!SimpleCaptcha::validate($_POST['captcha'] ?? '')) {
        $error = "Incorrect answer to math question.";
    }
    else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        // Validation
        if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email)) {
            $error = "All fields are required";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check password strength
            $passwordErrors = validatePasswordStrength($password);
            if (!empty($passwordErrors)) {
                $error = implode("<br>", $passwordErrors);
            } else {
                // Check if username exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Username or email already exists";
                } else {
                    // Hash password and insert user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$username, $hashed_password, $full_name, $email])) {
                        $success = "Registration successful! You can now login.";
                        $_POST = [];
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            }
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
    <title>Register - Suwan's Fund Manager</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="register-container px-4">
        <div class="text-center mb-8">
            <div class="text-5xl mb-4">💰</div>
            <h1 class="text-3xl font-light text-gray-800">Create Account</h1>
            <p class="text-gray-600 mt-2">Join Suwan's Fund Manager</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-box">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success-box">
            <?php echo escape($success); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <?php echo CSRF::getTokenField(); ?>
            
            <div>
                <input type="text" name="full_name" required placeholder="Full Name" 
                       value="<?php echo escape($_POST['full_name'] ?? ''); ?>"
                       class="input-underline">
            </div>
            
            <div>
                <input type="email" name="email" required placeholder="Email Address" 
                       value="<?php echo escape($_POST['email'] ?? ''); ?>"
                       class="input-underline">
            </div>
            
            <div>
                <input type="text" name="username" required placeholder="Username" 
                       value="<?php echo escape($_POST['username'] ?? ''); ?>"
                       class="input-underline">
            </div>
            
            <div>
                <input type="password" name="password" id="password" required 
                       placeholder="Password (min. 8 chars, uppercase, lowercase, number)"
                       onkeyup="checkPasswordStrength(); checkPasswordMatch();"
                       class="input-underline">
                <div class="password-strength bg-gray-200">
                    <div id="passwordStrength" class="password-strength" style="width: 0%;"></div>
                </div>
                <div id="passwordHint" class="text-xs mt-1 text-gray-600"></div>
            </div>
            
            <div>
                <input type="password" name="confirm_password" id="confirm_password" required 
                       placeholder="Confirm Password"
                       onkeyup="checkPasswordMatch();"
                       class="input-underline">
                <div id="passwordMatch" class="text-sm mt-1"></div>
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
                    class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium text-lg transition duration-200">
                Create Account
            </button>
        </form>
        
        <div class="text-center mt-8 pt-6 border-t border-gray-200">
            <p class="text-gray-600">
                Already have an account? 
                <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Login here
                </a>
            </p>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
