<?php
/**
 * Fix Display Names Script
 * 
 * This script checks for users without display_name and adds it
 */

// Include the autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Define constants
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
define('SRC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'src');

// Check if MongoDB PHP extension is installed
if (!extension_loaded('mongodb')) {
    die("MongoDB extension is not installed or not enabled in PHP\n");
}

try {
    echo "Starting display name fix...\n";
    
    // Create a direct connection to MongoDB for this maintenance script
    $mongoHost = 'mongodb://localhost:27017';
    $mongoDb = 'booking_system';
    $client = new MongoDB\Client($mongoHost);
    $usersCollection = $client->selectDatabase($mongoDb)->selectCollection('users');
    
    // Get all users without display_name
    $usersWithoutDisplayName = $usersCollection->find([
        'display_name' => ['$exists' => false]
    ]);
    
    $updatedCount = 0;
    
    // Iterate through users and update each one
    foreach ($usersWithoutDisplayName as $user) {
        $userId = (string)$user['_id'];
        $username = $user['username'] ?? 'User';
        
        echo "Updating user $userId ($username) with display_name\n";
        
        // Set display_name equal to username if empty
        $result = $usersCollection->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['display_name' => $username]]
        );
        
        if ($result->getModifiedCount() > 0) {
            $updatedCount++;
        }
    }
    
    echo "Done! Updated $updatedCount users with missing display_name\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}