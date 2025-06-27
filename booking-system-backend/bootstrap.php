<?php
/**
 * Bootstrap file for loading environment variables
 */

// Set error reporting - disable display for production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Require composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Include Email Config class
require_once __DIR__ . '/src/Utils/Email/EmailConfig.php';

// Load configuration from .env file
use App\Utils\Email\EmailConfig;
use Dotenv\Dotenv;

// Set base path (used by some functions)
define('BASE_PATH', __DIR__);

// Load main configuration
require_once __DIR__ . '/config/config.php';

// Load .env configuration using our custom loader
EmailConfig::load();

// Also load with vlucas/phpdotenv for compatibility with existing code
if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } catch (\Exception $e) {
        // Log but continue if .env cannot be loaded with Dotenv
        error_log("Bootstrap: Warning - Could not load .env with Dotenv: " . $e->getMessage());
    }
}

// Set default timezone
date_default_timezone_set('UTC');

// Log bootstrap
error_log("Bootstrap: Loading application configuration");

// Create necessary constant definitions
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('APP_ENV')) {
    define('APP_ENV', EmailConfig::get('APP_ENV', 'production'));
}
if (!defined('DEBUG')) {
    define('DEBUG', filter_var(EmailConfig::get('DEBUG', false), FILTER_VALIDATE_BOOLEAN));
}

// Create helper function to get config values
if (!function_exists('config')) {
    /**
     * Get a configuration value from ENV
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    function config($key, $default = null) {
        // Try getenv first for compatibility
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        // Then try EmailConfig
        return EmailConfig::get($key, $default);
    }
}

// Log completion
error_log("Bootstrap: Configuration loaded successfully");

// Explicitly set environment variables
// This ensures they are available via getenv() even in environments 
// where $_ENV might not be automatically mapped
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

// Debug log
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    $lines = explode("\n", $content);
    $envVariables = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue; // Skip empty lines and comments
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $envVariables[$name] = getenv($name);
        }
    }
    
    // Log that variables were loaded
    error_log("Bootstrap: Loaded " . count($envVariables) . " environment variables from .env");
    
    // Check critical variables
    if (isset($envVariables['SENDGRID_API_KEY'])) {
        error_log("Bootstrap: SendGrid API key found with length " . strlen($envVariables['SENDGRID_API_KEY']));
    } else {
        error_log("Bootstrap: SendGrid API key NOT found");
    }
}

// Define base paths
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
}
if (!defined('SRC_PATH')) {
    define('SRC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'src');
} 