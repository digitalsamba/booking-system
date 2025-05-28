<?php
/**
 * Application entry point
 * 
 * Routes requests to the appropriate controller
 */

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration should already be loaded from router.php
if (!defined('BASE_PATH')) {
    die("BASE_PATH not defined. This file should be loaded through router.php");
}

// MongoDB should already be loaded from router.php
if (!class_exists('\MongoDB\Client')) {
    die("MongoDB classes not loaded. This file should be loaded through router.php");
}

// Log the request for debugging
error_log("Processing request: " . $_SERVER['REQUEST_URI']);

// Handle static file serving for uploads
$requestUri = $_SERVER['REQUEST_URI'];
if (strpos($requestUri, '/uploads/') === 0) {
    $filePath = __DIR__ . $requestUri;
    if (file_exists($filePath)) {
        // Determine MIME type
        $mimeType = mime_content_type($filePath);
        if (!$mimeType) {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf'
            ];
            $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
        }
        
        // Set appropriate headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        
        // Output the file
        readfile($filePath);
        exit;
    } else {
        // File not found
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['error' => 'File not found']);
        exit;
    }
}

// Autoload App classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    
    // Check if the class uses the prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Build the file path with proper directory separators
    $file = SRC_PATH . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relative_class) . '.php';
    
    // Log the file path for debugging
    error_log("Looking for class file: $file");
    
    // Require the file if it exists
    if (file_exists($file)) {
        require_once $file;
        error_log("Class file loaded: $file");
    } else {
        error_log("Class file not found: $file");
    }
});

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Parse the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Split URI by slashes
$uriParts = explode('/', $uri);
error_log("URI parts: " . print_r($uriParts, true));

// Handle public routes separately (no authentication needed)
if (isset($uriParts[0]) && $uriParts[0] === 'public') {
    $controller = 'Public';
    $action = !empty($uriParts[1]) ? $uriParts[1] : 'index';
} else {
    // Determine controller and action for regular routes
    $controller = !empty($uriParts[0]) ? ucfirst($uriParts[0]) : 'Default';
    $action = !empty($uriParts[1]) ? $uriParts[1] : 'index';
}

error_log("Controller: $controller, Action: $action");

// Construct the controller class name
$controllerClass = "App\\Controllers\\{$controller}Controller";
error_log("Looking for controller class: $controllerClass");

// Try to load and execute the controller action
try {
    if (!class_exists($controllerClass)) {
        throw new \Exception("Controller not found: $controllerClass");
    }
    
    $controllerInstance = new $controllerClass();
    
    if (!method_exists($controllerInstance, $action)) {
        throw new \Exception("Action not found: $action in controller $controller");
    }
    
    // Call the controller action
    $controllerInstance->$action();
    
} catch (\Exception $e) {
    // Log the error
    error_log("Error: " . $e->getMessage());
    
    // Use Response utility if available
    if (class_exists('App\\Utils\\Response')) {
        \App\Utils\Response::json(['error' => $e->getMessage()], 404);
    } else {
        // Fallback if Response class isn't available
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}