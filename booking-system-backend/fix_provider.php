<?php
/**
 * Provider Database Utility
 * 
 * This script lists all providers and can create a missing provider with a specific ID
 */

// Load bootstrap to get MongoDB connection info
require_once __DIR__ . '/bootstrap.php';

// Display header
echo "======================================================\n";
echo "PROVIDER DATABASE UTILITY\n";
echo "======================================================\n\n";

// Connect to MongoDB
try {
    $mongoHost = 'mongodb://localhost:27017';
    $mongoDb = 'booking_system';
    
    echo "Connecting to MongoDB at {$mongoHost}...\n";
    
    // Connect to MongoDB
    $client = new MongoDB\Client($mongoHost);
    $database = $client->selectDatabase($mongoDb);
    $providersCollection = $database->selectCollection('providers');
    
    echo "Successfully connected to MongoDB\n\n";
    
    // List all existing providers
    echo "EXISTING PROVIDERS:\n";
    echo "------------------------------------------------------\n";
    
    $providers = $providersCollection->find([]);
    $providersFound = false;
    
    foreach ($providers as $provider) {
        $providersFound = true;
        $id = (string)$provider['_id'];
        $name = $provider['display_name'] ?? $provider['username'] ?? 'Unknown';
        
        echo "ID: {$id}\n";
        echo "Name: {$name}\n";
        echo "------------------------------------------------------\n";
    }
    
    if (!$providersFound) {
        echo "No providers found in the database.\n";
        echo "------------------------------------------------------\n\n";
    }
    
    // Create the missing provider if needed
    $missingProviderId = '680a5b45bf333c7c680beb52';
    echo "Checking for provider with ID: {$missingProviderId}...\n";
    
    // Check if provider exists
    $provider = $providersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($missingProviderId)]);
    
    if ($provider) {
        echo "Provider already exists! No need to create it.\n";
    } else {
        echo "Provider does not exist. Would you like to create it? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        
        if (strtolower($line) === 'y') {
            // Create a new provider with the specific ID
            $newProvider = [
                '_id' => new MongoDB\BSON\ObjectId($missingProviderId),
                'username' => 'conal4', // This should match what your frontend is using
                'display_name' => 'Conal Provider',
                'email' => 'provider@example.com',
                'team_id' => '12345',  // Add actual Digital Samba team_id if available
                'developer_key' => 'devkey123', // Add actual Digital Samba developer_key if available
                'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000),
                'updated_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
            ];
            
            $result = $providersCollection->insertOne($newProvider);
            
            if ($result->getInsertedCount() > 0) {
                echo "Successfully created provider with ID: {$missingProviderId}\n";
            } else {
                echo "Failed to create provider.\n";
            }
        } else {
            echo "Provider creation canceled.\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
