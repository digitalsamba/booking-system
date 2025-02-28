<?php
// MongoDB integration test
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Manual autoload for App classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    // Test Database utility class
    $collection = \App\Utils\Database::getCollection('test');
    
    // Insert a test document
    $result = $collection->insertOne([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'created_at' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
    ]);
    
    echo "Document inserted with ID: " . $result->getInsertedId() . "\n";
    
    // Find the document
    $document = $collection->findOne(['email' => 'test@example.com']);
    
    echo "Found document:\n";
    print_r($document);
    
    // Clean up
    $collection->deleteMany(['email' => 'test@example.com']);
    echo "Test documents cleaned up.\n";
    
    echo "MongoDB integration test completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}