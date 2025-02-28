<?php
/**
 * Session Utility
 * 
 * Handles PHP sessions with security best practices
 */

namespace App\Utils;

class Session {
    /**
     * Start a secure session
     *
     * @return bool True if session started successfully
     */
    public function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Use secure cookies in production environment
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
                ini_set('session.cookie_samesite', 'Strict');
            }
            
            // Set session name and lifetime
            session_name(SESSION_NAME);
            session_set_cookie_params(SESSION_LIFETIME);
            
            return session_start();
        }
        
        return true;
    }
    
    /**
     * Set a session variable
     *
     * @param string $key The variable name
     * @param mixed $value The variable value
     * @return void
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get a session variable
     *
     * @param string $key The variable name
     * @param mixed $default Default value if not set
     * @return mixed The session variable value
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Remove a session variable
     *
     * @param string $key The variable name
     * @return void
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Check if a session variable exists
     *
     * @param string $key The variable name
     * @return bool True if exists, false otherwise
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Regenerate session ID
     *
     * @param bool $deleteOldSession Whether to delete the old session
     * @return bool True if regenerated successfully
     */
    public function regenerateId($deleteOldSession = true) {
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Destroy the session
     *
     * @return bool True if destroyed successfully
     */
    public function destroy() {
        // Clear session array
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session
        return session_destroy();
    }
}