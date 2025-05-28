<?php
// Check MongoDB users
require_once __DIR__ . '/booking-system-backend/vendor/autoload.php';

use MongoDB\Client;

try {
    // Load database config
    $dbConfig = require __DIR__ . '/booking-system-backend/config/database.php';
    $mongoConfig = $dbConfig['mongodb'];
    
    // Build connection string
    $uri = sprintf(
        'mongodb://%s%s%s:%d/%s',
        $mongoConfig['username'] ? $mongoConfig['username'] . ':' : '',
        $mongoConfig['password'] ? $mongoConfig['password'] . '@' : '',
        $mongoConfig['host'],
        $mongoConfig['port'],
        $mongoConfig['database']
    );
    
    // Connect to MongoDB
    $client = new Client($uri);
    $database = $client->selectDatabase($mongoConfig['database']);
    $collection = $database->selectCollection('users');
    
    // Find all users
    $users = $collection->find()->toArray();
    
    echo "Found " . count($users) . " users:\n\n";
    
    foreach ($users as $user) {
        echo "ID: " . $user['_id'] . "\n";
        echo "Username: " . ($user['username'] ?? 'N/A') . "\n";
        echo "Email: " . ($user['email'] ?? 'N/A') . "\n";
        echo "Display Name: " . ($user['display_name'] ?? 'N/A') . "\n";
        echo "Password Hash: " . substr($user['password'] ?? '', 0, 20) . "...\n";
        echo "Created: " . (isset($user['created_at']) ? $user['created_at']->toDateTime()->format('Y-m-d H:i:s') : 'N/A') . "\n";
        echo "---\n";
    }
    
    // Also check availability collection
    echo "\n\nChecking availability collection:\n";
    $availCollection = $database->selectCollection('availability');
    $availCount = $availCollection->countDocuments();
    echo "Found $availCount availability slots\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
