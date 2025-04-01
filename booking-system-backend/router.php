<?php
/**
 * Simple router for PHP's built-in server
 */

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Set CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');  // 24 hours
    
    // Return 200 OK with no content
    http_response_code(200);
    exit;
}

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

// Public API endpoints
if (preg_match('#^public/availability$#', $uri)) {
    $controller = new \App\Controllers\PublicController();
    $controller->availability();
    exit;
}

if (preg_match('#^public/booking$#', $uri)) {
    $controller = new \App\Controllers\PublicController();
    $controller->booking();
    exit;
}

// Provider details endpoint
if (preg_match('#^providers/([^/]+)$#', $uri, $matches)) {
    $controller = new \App\Controllers\PublicController();
    $controller->getProviderDetails($matches[1]);
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

// Profile management
if (preg_match('#^auth/profile$#', $uri)) {
    $controller = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller->getProfile();
    } else if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->updateProfile();
    }
    exit;
}

// Add these routes right before the "For all other routes..." section

// Digital Samba meeting links routes
if (preg_match('#^booking/([^/]+)/meeting-links$#', $uri, $matches)) {
    $bookingId = $matches[1];
    
    try {
        $controller = new \App\Controllers\DigitalSambaController();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $_SERVER['PATH_INFO'] = "/booking/{$bookingId}/meeting-links";
            $controller->getMeetingLinks($bookingId);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SERVER['PATH_INFO'] = "/booking/{$bookingId}/meeting-links";
            $controller->generateMeetingLinks($bookingId);
        }
    } catch (\Exception $e) {
        error_log("Error handling meeting links request: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Failed to process request: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Debug endpoint for authentication troubleshooting
if ($uri === 'auth-debug') {
    include_once __DIR__ . '/public/auth_debug.php';
    exit;
}

// For all other routes, include the main application entry point
include_once __DIR__ . '/public/index.php';