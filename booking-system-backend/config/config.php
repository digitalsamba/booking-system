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

// Set error reporting in development environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
define('SESSION_NAME', 'booking_system_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// JWT configuration for API authentication
/**
 * IMPORTANT: DO NOT CHANGE THE JWT_SECRET VALUE ONCE SET IN PRODUCTION
 * Changing this value will invalidate all existing tokens
 */
// Update this with a strong, consistent secret
// IMPORTANT: If you change this, all existing tokens will be invalidated
define('JWT_SECRET', 'your-very-secure-jwt-secret-key');
define('JWT_EXPIRY', 86400); // 24 hours in seconds

// CORS Settings
define('ALLOW_ORIGIN', '*'); // Set to specific domain in production
define('ALLOW_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('ALLOW_HEADERS', 'Content-Type, Authorization, X-Requested-With');