<?php
/**
 * Database configuration
 * 
 * Contains connection parameters for MongoDB
 */

// Load environment helper if not already loaded
if (!function_exists('env')) {
    require_once __DIR__ . '/env.php';
}

// MongoDB connection parameters
return [
    'mongodb' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 27017),
        'database' => env('DB_DATABASE', 'booking_system'),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
        'options' => [
            'retryWrites' => true
        ]
    ]
];