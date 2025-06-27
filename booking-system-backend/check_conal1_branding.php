<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new \MongoDB\Client("mongodb://localhost:27017");
    $database = $client->booking_system;
    $brandingCollection = $database->branding_settings;
    $usersCollection = $database->users;
    
    // First, let's find the conal1 user
    echo "Looking for conal1 user...\n";
    $user = $usersCollection->findOne(['username' => 'conal1']);
    if ($user) {
        echo "Found user: " . json_encode($user, JSON_PRETTY_PRINT) . "\n";
        $userId = (string)$user->_id;
        echo "User ID: $userId\n";
        
        // Now find branding settings
        echo "\nLooking for branding settings...\n";
        $branding = $brandingCollection->findOne(['userId' => $userId]);
        if ($branding) {
            echo "Found branding settings: " . json_encode($branding, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "No branding settings found for user $userId\n";
        }
    } else {
        echo "User conal1 not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>