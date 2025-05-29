<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new \MongoDB\Client("mongodb://localhost:27017");
    $database = $client->booking_system;
    $brandingCollection = $database->branding_settings;
    
    echo "All branding settings in database:\n";
    $cursor = $brandingCollection->find([]);
    $count = 0;
    foreach ($cursor as $doc) {
        $count++;
        echo "Document $count: " . json_encode($doc, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    if ($count === 0) {
        echo "No branding settings found in the database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>