<?php
/**
 * Database configuration
 * 
 * Contains connection parameters for MongoDB
 */

// MongoDB connection parameters
return [
    'mongodb' => [
        'host' => 'localhost',
        'port' => 27017,
        'database' => 'booking_system',
        'username' => '',
        'password' => '',
        'options' => [
            'retryWrites' => true
        ]
    ]
];