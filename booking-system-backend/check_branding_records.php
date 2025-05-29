<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new \MongoDB\Client("mongodb://localhost:27017");
    $database = $client->booking_system;
    $brandingCollection = $database->branding_settings;
    
    // Get the current branding record for conal1
    echo "Current branding records:\n";
    $cursor = $brandingCollection->find([]);
    foreach ($cursor as $doc) {
        echo "User ID: " . json_encode($doc['userId']) . "\n";
        echo "Logo URL: " . $doc['logoUrl'] . "\n";
        echo "Document: " . json_encode($doc, JSON_PRETTY_PRINT) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>