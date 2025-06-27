<?php
// Debug test endpoint
header('Content-Type: application/json');

// Log request details
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP_HOST: " . $_SERVER['HTTP_HOST']);

// Check if we can load the bootstrap
try {
    require_once __DIR__ . '/bootstrap.php';
    echo json_encode([
        'status' => 'ok',
        'message' => 'Bootstrap loaded successfully',
        'path' => __DIR__,
        'constants' => [
            'BASE_PATH' => defined('BASE_PATH') ? BASE_PATH : 'not defined',
            'JWT_SECRET' => defined('JWT_SECRET') ? 'defined' : 'not defined'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
