<?php
/**
 * Database configuration
 * 
 * Contains connection parameters for MongoDB
 * Uses constants defined in config.php
 */

// Make sure config.php is included before using this file
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// MongoDB connection parameters
return [
    'mongodb' => [
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'options' => [
            'retryWrites' => true
        ]
    ]
];