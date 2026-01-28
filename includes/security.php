<?php
// CSRF Token - Prevents fake form submissions
class CSRF {
    // Generate token
    public static function generate() {
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['token'];
    }
    
    // Check if token is valid
    public static function check($token) {
        return isset($_SESSION['token']) && $token === $_SESSION['token'];
    }
    
    // Get hidden input field
    public static function field() {
        $token = self::generate();
        return '<input type="hidden" name="token" value="' . $token . '">';
    }
}

// CAPTCHA - Simple math question
class CAPTCHA {
    // Create math question
    public static function create() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha'] = $num1 + $num2;
        return "$num1 + $num2";
    }
    
    // Check answer
    public static function check($answer) {
        if (!isset($_SESSION['captcha'])) return false;
        $correct = (int)$_SESSION['captcha'] === (int)$answer;
        unset($_SESSION['captcha']);
        return $correct;
    }
}

// Clean user input
function clean($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>