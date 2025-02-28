<?php
/**
 * Simple router for PHP's built-in server
 */

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// IMPORTANT: Load Composer autoloader at the very beginning
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die("Composer autoloader not found. Please run 'composer install'");
}

// Define base paths only if not defined yet
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
}

if (!defined('SRC_PATH')) {
    define('SRC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'src');
}

// Log incoming request for debugging
error_log("Incoming request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Check if the request is for a static file (HTML, CSS, JS, images)
$staticExtensions = ['html', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg'];
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$extension = pathinfo($requestPath, PATHINFO_EXTENSION);

if (in_array($extension, $staticExtensions)) {
    // For static files in /public directory
    $filePath = __DIR__ . '/public' . $requestPath;
    
    // If file doesn't exist in /public, try the root directory
    if (!file_exists($filePath)) {
        $filePath = __DIR__ . $requestPath;
    }
    
    // If the file exists, serve it with appropriate content type
    if (file_exists($filePath)) {
        $contentType = 'text/plain';
        
        // Set content type based on file extension
        switch ($extension) {
            case 'html': $contentType = 'text/html'; break;
            case 'css': $contentType = 'text/css'; break;
            case 'js': $contentType = 'application/javascript'; break;
            case 'png': $contentType = 'image/png'; break;
            case 'jpg': case 'jpeg': $contentType = 'image/jpeg'; break;
            case 'gif': $contentType = 'image/gif'; break;
            case 'ico': $contentType = 'image/x-icon'; break;
            case 'svg': $contentType = 'image/svg+xml'; break;
        }
        
        header('Content-Type: ' . $contentType);
        readfile($filePath);
        exit;
    }
}

// Direct routes for system checks
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Simple routes for testing API availability
if ($uri === 'ping' || $uri === 'test') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => time(),
        'route' => $uri
    ]);
    exit;
}

// Booking routes - public endpoints
if (preg_match('#^booking/create$#', $uri)) {
    $controller = new \App\Controllers\BookingController();
    $controller->create();
    exit;
}

// Booking routes - authenticated endpoints
if (preg_match('#^bookings$#', $uri)) {
    $controller = new \App\Controllers\BookingController();
    $controller->index();
    exit;
}

if (preg_match('#^booking/([^/]+)$#', $uri, $matches)) {
    $controller = new \App\Controllers\BookingController();
    $_SERVER['PATH_INFO'] = '/booking/' . $matches[1];
    $controller->details();
    exit;
}

if (preg_match('#^booking/([^/]+)/cancel$#', $uri, $matches)) {
    $controller = new \App\Controllers\BookingController();
    $_SERVER['PATH_INFO'] = '/booking/' . $matches[1] . '/cancel';
    $controller->cancel();
    exit;
}

// Auth routes
if (preg_match('#^auth/login$#', $uri)) {
    $controller = new \App\Controllers\AuthController();
    $controller->login();
    exit;
}

if (preg_match('#^auth/register$#', $uri)) {
    $controller = new \App\Controllers\AuthController();
    $controller->register();
    exit;
}

// Add this new route for token generation
if (preg_match('#^auth/new-token$#', $uri)) {
    $controller = new \App\Controllers\AuthController();
    $controller->newToken();
    exit;
}

// For all other routes, include the main application entry point
include_once __DIR__ . '/public/index.php';