<?php
/**
 * Check User Script
 * 
 * Examines if user conal4 exists and has Digital Samba credentials
 */

// Load bootstrap to get MongoDB connection info
require_once __DIR__ . '/bootstrap.php';

// Connect to MongoDB
try {
    $mongoHost = 'mongodb://localhost:27017';
    $mongoDb = 'booking_system';
    
    echo "Connecting to MongoDB at {$mongoHost}...\n";
    
    // Connect to MongoDB
    $client = new MongoDB\Client($mongoHost);
    $database = $client->selectDatabase($mongoDb);
    $usersCollection = $database->selectCollection('users');
    
    echo "Connected to MongoDB\n\n";
    
    // Find user by username
    echo "Searching for user 'conal4'...\n";
    $user = $usersCollection->findOne(['username' => 'conal4']);
    
    if ($user) {
        echo "Found user 'conal4'!\n";
        echo "User ID: " . (string)$user['_id'] . "\n\n";
        
        echo "== User Data ==\n";
        echo "Username: " . ($user['username'] ?? 'Not set') . "\n";
        echo "Display Name: " . ($user['display_name'] ?? 'Not set') . "\n";
        echo "Digital Samba team_id: " . ($user['team_id'] ?? 'Not set') . "\n";
        echo "Digital Samba developer_key: " . 
            (isset($user['developer_key']) ? substr($user['developer_key'], 0, 5) . '...' : 'Not set') . "\n";
        
        echo "\nChecking if this matches our provider ID...\n";
        $providerId = '680a5b45bf333c7c680beb52';
        echo "Booking provider_id: $providerId\n";
        echo "User _id: " . (string)$user['_id'] . "\n";
        
        if ((string)$user['_id'] === $providerId) {
            echo "\n✅ MATCH! The user ID matches the provider_id in bookings\n";
        } else {
            echo "\n❌ NOT A MATCH! The user ID does not match the provider_id in bookings\n";
        }
    } else {
        echo "User 'conal4' not found in the users collection.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
