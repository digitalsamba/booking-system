<?php
// Include Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Check MongoDB PHP library installation
echo "MongoDB PHP Library Check\n";

// Check if MongoDB extension is loaded
echo "MongoDB extension loaded: " . (extension_loaded('mongodb') ? 'Yes' : 'No') . "\n";

// Check required classes
echo "MongoDB\Client class exists: " . (class_exists('\MongoDB\Client') ? 'Yes' : 'No') . "\n";
echo "MongoDB\Database class exists: " . (class_exists('\MongoDB\Database') ? 'Yes' : 'No') . "\n";
echo "MongoDB\Collection class exists: " . (class_exists('\MongoDB\Collection') ? 'Yes' : 'No') . "\n";

// Try connecting to MongoDB
try {
    $client = new \MongoDB\Client("mongodb://localhost:27017");
    echo "MongoDB connection successful!\n";
    
    // List databases
    echo "\nAvailable databases:\n";
    $databaseList = $client->listDatabases();
    foreach ($databaseList as $database) {
        echo "- " . $database->getName() . "\n";
    }
} catch (Exception $e) {
    echo "MongoDB connection error: " . $e->getMessage() . "\n";
}