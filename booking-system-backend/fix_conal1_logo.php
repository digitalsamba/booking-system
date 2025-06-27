<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new \MongoDB\Client("mongodb://localhost:27017");
    $database = $client->booking_system;
    $brandingCollection = $database->branding_settings;
    
    $userId = new MongoDB\BSON\ObjectId("68373d5d8d5126dbc5008c84"); // conal1's user ID
    
    // Update the logoUrl to point to the uploaded logo
    $newLogoUrl = "/uploads/logos/conal1-logo.png";
    
    echo "Updating logoUrl for user $userId to: $newLogoUrl\n";
    
    $result = $brandingCollection->updateOne(
        ['userId' => $userId],
        ['$set' => ['logoUrl' => $newLogoUrl, 'updatedAt' => new MongoDB\BSON\UTCDateTime()]]
    );
    
    if ($result->getModifiedCount() > 0) {
        echo "Successfully updated logoUrl!\n";
        
        // Verify the update
        $branding = $brandingCollection->findOne(['userId' => $userId]);
        echo "New branding settings: " . json_encode($branding, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No document was modified. Check if the user ID exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>