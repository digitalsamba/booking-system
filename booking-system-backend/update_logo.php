<?php
/**
 * Update Logo URL for conal1 user
 */

require_once __DIR__ . '/bootstrap.php';

try {
    $mongoHost = 'mongodb://localhost:27017';
    $mongoDb = 'booking_system';
    
    echo "Connecting to MongoDB at {$mongoHost}...\n";
    
    $client = new MongoDB\Client($mongoHost);
    $database = $client->selectDatabase($mongoDb);
    $brandingCollection = $database->selectCollection('branding_settings'); // Fixed collection name
    
    echo "Connected to MongoDB\n\n";
    
    // List all collections to see what exists
    echo "Available collections:\n";
    $collections = $database->listCollections();
    foreach ($collections as $collection) {
        echo "- " . $collection->getName() . "\n";
    }
    echo "\n";
    
    // Check the branding collection
    echo "Branding collection document count: " . $brandingCollection->countDocuments() . "\n\n";
    
    // First, let's see what records exist in the branding collection
    echo "All branding records:\n";
    $allBranding = $brandingCollection->find();
    foreach ($allBranding as $record) {
        echo "Record ID: " . $record['_id'] . ", UserID: " . $record['userId'] . " (type: " . gettype($record['userId']) . ")\n";
    }
    echo "\n";
    
    // Find branding record for conal1 user
    $userId = '68373d5d8d5126dbc5008c84'; // From the API response we saw earlier
    
    // Try both string and ObjectId formats
    $existingBranding = $brandingCollection->findOne(['userId' => $userId]);
    
    if (!$existingBranding) {
        // Try with ObjectId
        try {
            $objectId = new MongoDB\BSON\ObjectId($userId);
            $existingBranding = $brandingCollection->findOne(['userId' => $objectId]);
            echo "Found record using ObjectId format\n";
        } catch (Exception $e) {
            echo "Error creating ObjectId: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Found record using string format\n";
    }
    
    if ($existingBranding) {
        echo "Found existing branding record:\n";
        print_r($existingBranding);
        
        // Use the existing frontend logo as a test
        $logoUrl = 'http://localhost:3002/assets/digitalsamba-logo.svg'; // Use existing working logo
        
        $result = $brandingCollection->updateOne(
            ['userId' => new MongoDB\BSON\ObjectId($userId)],
            ['$set' => [
                'logoUrl' => $logoUrl,
                'updatedAt' => date('Y-m-d H:i:s')
            ]]
        );
        
        if ($result->getModifiedCount() > 0) {
            echo "\nSuccessfully updated branding with logoUrl: {$logoUrl}\n";
        } else {
            echo "\nNo documents were modified\n";
        }
        
        // Show updated record
        $updatedBranding = $brandingCollection->findOne(['userId' => new MongoDB\BSON\ObjectId($userId)]);
        echo "\nUpdated branding record:\n";
        print_r($updatedBranding);
        
    } else {
        echo "No branding record found for user ID: {$userId}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>