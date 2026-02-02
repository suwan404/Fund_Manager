<?php
// Custom session handler
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set custom session path
            $sessionPath = dirname(__DIR__) . '/sessions';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
            session_save_path($sessionPath);
            
            session_start();
        }
    }
}

// Auto-start session
SessionManager::start();