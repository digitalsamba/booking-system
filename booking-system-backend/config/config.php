<?php
/**
 * Main configuration file
 */

// Define base paths only if not defined yet
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
}

if (!defined('SRC_PATH')) {
    define('SRC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'src');
}

// Load environment variables from .env file
$envFile = BASE_PATH . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Process variable definitions (KEY=VALUE)
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.+)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Set error reporting based on environment
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Session configuration
define('SESSION_NAME', 'booking_system_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// JWT configuration for API authentication
/**
 * IMPORTANT: DO NOT CHANGE THE JWT_SECRET VALUE ONCE SET IN PRODUCTION
 * Changing this value will invalidate all existing tokens
 */
// Get JWT secret from environment variable or use fallback (for development only)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'fallback-dev-jwt-secret-not-for-production');
define('JWT_EXPIRY', getenv('JWT_EXPIRY') ? (int)getenv('JWT_EXPIRY') : 86400); // 24 hours in seconds

// CORS Settings
define('ALLOW_ORIGIN', getenv('ALLOW_ORIGIN') ?: '*'); // Set to specific domain in production
define('ALLOW_METHODS', getenv('ALLOW_METHODS') ?: 'GET, POST, PUT, DELETE, OPTIONS');
define('ALLOW_HEADERS', getenv('ALLOW_HEADERS') ?: 'Content-Type, Authorization, X-Requested-With');

// Database settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ? (int)getenv('DB_PORT') : 27017);
define('DB_NAME', getenv('DB_NAME') ?: 'booking_system');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');