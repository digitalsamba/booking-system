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

// Load environment variables
require_once CONFIG_PATH . '/env.php';

// Load .env file based on environment
$envFile = BASE_PATH . '/.env';
if (defined('APP_ENV') && file_exists(BASE_PATH . '/.env.' . APP_ENV)) {
    $envFile = BASE_PATH . '/.env.' . APP_ENV;
}
loadEnv($envFile);

// Set error reporting based on environment
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

// Session configuration
define('SESSION_NAME', 'booking_system_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// JWT configuration for API authentication
/**
 * IMPORTANT: DO NOT CHANGE THE JWT_SECRET VALUE ONCE SET IN PRODUCTION
 * Changing this value will invalidate all existing tokens
 */
define('JWT_SECRET', env('JWT_SECRET', 'your-very-secure-jwt-secret-key'));
define('JWT_EXPIRY', env('JWT_EXPIRY', 86400)); // 24 hours in seconds

// CORS Settings
define('ALLOW_ORIGIN', env('CORS_ALLOWED_ORIGIN', '*')); // Set to specific domain in production
define('ALLOW_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('ALLOW_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Application settings
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('APP_URL', env('APP_URL', 'http://localhost:8080'));