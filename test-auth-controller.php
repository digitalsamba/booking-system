<?php
// Test Auth Controller directly
require_once __DIR__ . '/booking-system-backend/vendor/autoload.php';
require_once __DIR__ . '/booking-system-backend/bootstrap.php';

use App\Controllers\AuthController;

try {
    echo "Testing AuthController instantiation...\n";
    
    // Try to create controller
    $controller = new AuthController();
    echo "Controller created successfully\n";
    
    // Try to check if login method exists
    if (method_exists($controller, 'login')) {
        echo "Login method exists\n";
    } else {
        echo "Login method NOT found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
