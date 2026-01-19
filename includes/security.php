<?php
/**
 * Security Functions and Classes
 * Contains CSRF protection, CAPTCHA, input sanitization, and rate limiting
 */

// ========================================
// CSRF (Cross-Site Request Forgery) Protection
// ========================================
class CSRF {
    /**
     * Generate a unique CSRF token
     * Token is stored in session
     */
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            // Generate 32-byte random token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * Compares submitted token with session token
     */
    public static function validateToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
    
    /**
     * Get hidden input field with CSRF token
     * Use this in forms
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

// ========================================
// Simple CAPTCHA Implementation
// ========================================
class SimpleCaptcha {
    /**
     * Generate a simple math CAPTCHA
     * Creates addition problem like "5 + 3"
     */
    public static function generate() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        // Store answer in session
        $_SESSION['captcha_answer'] = $num1 + $num2;
        $_SESSION['captcha_question'] = "$num1 + $num2";
        return $_SESSION['captcha_question'];
    }
    
    /**
     * Validate CAPTCHA answer
     * Checks if user's answer matches stored answer
     */
    public static function validate($answer) {
        if (!isset($_SESSION['captcha_answer'])) {
            return false;
        }
        $correct = (int)$_SESSION['captcha_answer'] === (int)$answer;
        // Clear CAPTCHA after validation (one-time use)
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_question']);
        return $correct;
    }
}

// ========================================
// Input Sanitization
// ========================================
/**
 * Sanitize user input
 * Removes HTML tags and special characters
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        // Recursively sanitize arrays
        return array_map('sanitizeInput', $input);
    }
    // Remove HTML tags, trim whitespace, convert special characters
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// ========================================
// Output Escaping
// ========================================
/**
 * Escape output for display
 * Prevents XSS (Cross-Site Scripting) attacks
 */
function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ========================================
// Password Strength Validation
// ========================================
/**
 * Validate password strength
 * Returns array of error messages
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    // Check minimum length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

// ========================================
// Rate Limiting (Brute Force Protection)
// ========================================
class RateLimiter {
    /**
     * Get unique key for rate limiting
     */
    private static function getKey($identifier) {
        return 'rate_limit_' . md5($identifier);
    }
    
    /**
     * Check if action is allowed (not rate limited)
     * Default: 5 attempts per 5 minutes (300 seconds)
     */
    public static function check($identifier, $maxAttempts = 5, $period = 300) {
        $key = self::getKey($identifier);
        
        // Initialize if not exists
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if period expired
        if (time() - $data['start'] > $period) {
            $_SESSION[$key] = ['count' => 1, 'start' => time()];
            return true;
        }
        
        // Block if max attempts reached
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Get remaining lockout time in seconds
     */
    public static function getRemainingTime($identifier, $period = 300) {
        $key = self::getKey($identifier);
        if (!isset($_SESSION[$key])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION[$key]['start'];
        return max(0, $period - $elapsed);
    }
}
?>