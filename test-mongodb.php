<?php
// Simple MongoDB connection test
echo "Testing MongoDB connection...\n";

try {
    require_once __DIR__ . '/booking-system-backend/vendor/autoload.php';
    
    $client = new MongoDB\Client('mongodb://localhost:27017');
    
    // Set a timeout for the connection test
    $options = [
        'serverSelectionTimeoutMS' => 3000, // 3 second timeout
        'connectTimeoutMS' => 3000,
        'socketTimeoutMS' => 3000
    ];
    
    $client = new MongoDB\Client('mongodb://localhost:27017', [], $options);
    
    echo "Attempting to list databases...\n";
    $databases = $client->listDatabases();
    
    echo "MongoDB connection successful!\n";
    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "- " . $db->getName() . "\n";
    }
    
} catch (Exception $e) {
    echo "MongoDB connection failed: " . $e->getMessage() . "\n";
}
