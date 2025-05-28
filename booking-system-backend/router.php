<?php
/**
 * Main application router using FastRoute
 */

// Ensure autoloader is loaded first
require_once __DIR__ . '/vendor/autoload.php';

// --- Basic Setup ---
require_once __DIR__ . '/bootstrap.php';

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;
use App\Utils\Response; // Assuming this utility handles JSON output and status codes

// Set up error handling (optional here, might be better in bootstrap)
if (defined('DEBUG') && DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
}

// --- CORS Preflight Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Set CORS headers (Consider making origin more specific in production)
    header('Access-Control-Allow-Origin: *'); 
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
    http_response_code(204); // No Content for OPTIONS response
    exit;
}

// Set default CORS header for actual requests (adjust origin as needed)
header('Access-Control-Allow-Origin: *');

// --- FastRoute Dispatching ---

// 1. Create dispatcher
$routeDefinitionCallback = require CONFIG_PATH . '/routes.php';
// ADDED FOR DEBUGGING: Check if the callback was loaded
if (is_callable($routeDefinitionCallback)) {
    error_log("--> Route definition callback loaded successfully.");
} else {
    error_log("--> ERROR: Failed to load route definition callback from config/routes.php. Check file syntax and path.");
    // Optionally, exit here if routes are critical
    Response::json(['error' => 'Internal Server Error - Route Configuration Failed'], 500);
    exit;
}
$dispatcher = simpleDispatcher($routeDefinitionCallback);

// 2. Get HTTP method and URI
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// Get URI path, remove query string, decode
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// IMPORTANT: Handle potential /api prefix removal.
// The routes in routes.php are defined *without* the /api prefix because
// we assume the frontend proxy (Vite) or webserver (Nginx/Apache) 
// strips it before passing the request to this PHP script.
// If this script *is* receiving URIs like /api/bookings, we need to strip it here:
// Example: if (strpos($uri, 'api/') === 0) { $uri = substr($uri, 4); }
// For now, we assume the prefix is already removed and routes match directly (e.g., 'bookings', 'auth/login')
error_log("FastRoute Dispatching: Method={$httpMethod}, URI='{$uri}'");

// 3. Dispatch the route
error_log("--> Attempting to dispatch URI: [" . $uri . "]"); // ADDED FOR DEBUGGING
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 4. Handle dispatch result
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        error_log("FastRoute Result: 404 Not Found for URI '{$uri}'");
        Response::json(['error' => 'Not Found'], 404);
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        error_log("FastRoute Result: 405 Method Not Allowed for URI '{$uri}'. Allowed: " . implode(', ', $allowedMethods));
        header('Allow: ' . implode(', ', $allowedMethods)); // Set Allow header
        Response::json(['error' => 'Method Not Allowed'], 405);
        break;

    case Dispatcher::FOUND:
        $handler = $routeInfo[1]; // [Controller::class, 'methodName'] or Closure
        $vars = $routeInfo[2];    // Route parameters (e.g., ['id' => '123'])
        
        error_log("FastRoute Result: Found route for URI '{$uri}'. Handler: " . json_encode($handler) . ", Vars: " . json_encode($vars));
        
        try {
            // Check if handler is a Closure (for simple routes like /ping)
            if ($handler instanceof Closure) {
                $handler($vars); // Call the closure
            } 
            // Check if handler is [Controller, Method]
            elseif (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
                $controllerClass = $handler[0];
                $method = $handler[1];

                if (class_exists($controllerClass)) {
                    // Instantiate controller (assumes no constructor args needed based on current pattern)
                    // If DI is needed later, this is where it would happen.
                    $controller = new $controllerClass();

                    if (method_exists($controller, $method)) {
                        // Call the controller method, passing route parameters as arguments
                        // Uses reflection to match parameters if needed, but simple sequential passing works too
                        // For simplicity, passing $vars array - method needs to handle it or use func_get_args()
                        // A better approach uses reflection or passes named args.
                        // Let's pass named args directly if possible (PHP 8+)
                        // $controller->$method(...array_values($vars)); // Simple sequential passing
                        $controller->$method(...$vars); // Pass named parameters (requires PHP 8 named args)
                    } else {
                        error_log("FastRoute Error: Method '{$method}' not found in controller '{$controllerClass}'");
                        Response::json(['error' => 'Internal Server Error - Method Not Found'], 500);
                    }
                } else {
                    error_log("FastRoute Error: Controller class '{$controllerClass}' not found");
                    Response::json(['error' => 'Internal Server Error - Controller Not Found'], 500);
                }
            } else {
                error_log("FastRoute Error: Invalid handler format for route '{$uri}'. Handler: " . json_encode($handler));
                Response::json(['error' => 'Internal Server Error - Invalid Route Configuration'], 500);
            }
        } catch (\Throwable $e) { // Catch Throwable for PHP 7+ errors
            error_log("FastRoute Execution Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack Trace: \n" . $e->getTraceAsString());
            // Use a generic error in production
            $errorMsg = (defined('DEBUG') && DEBUG) ? $e->getMessage() : 'Internal Server Error';
            Response::json(['error' => $errorMsg], 500);
        }
        break;
}

// No need for exit() here as controllers/Response::json handle it