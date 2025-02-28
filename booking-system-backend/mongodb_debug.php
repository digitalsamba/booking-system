<?php
// mongodb_debug.php - Script to debug MongoDB autoloading issues

echo "=== MongoDB PHP Library Debug ===\n\n";

// Check if Composer's autoloader exists
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "Composer autoloader found at: $autoloadPath\n";
    require_once $autoloadPath;
    echo "Composer autoloader loaded successfully.\n";
} else {
    echo "ERROR: Composer autoloader NOT found at: $autoloadPath\n";
    echo "Run 'composer install' to install dependencies.\n\n";
    exit(1);
}

// Check if MongoDB extension is loaded
echo "\nMongoDB extension loaded: " . (extension_loaded('mongodb') ? 'Yes' : 'No') . "\n";

// Check for MongoDB classes
$mongoClasses = [
    '\MongoDB\Client',
    '\MongoDB\Database',
    '\MongoDB\Collection',
    '\MongoDB\BSON\ObjectId'
];

echo "\nChecking MongoDB classes:\n";
$allClassesFound = true;
foreach ($mongoClasses as $class) {
    $exists = class_exists($class);
    echo "- $class: " . ($exists ? 'Found' : 'NOT FOUND') . "\n";
    if (!$exists) {
        $allClassesFound = false;
    }
}

if (!$allClassesFound) {
    echo "\nSome MongoDB classes are missing!\n";
    echo "Check that the MongoDB PHP Library is properly installed:\n";
    echo "Run: composer show mongodb/mongodb\n\n";
} else {
    echo "\nAll MongoDB classes are available.\n";
}

// Check PHP's include_path
echo "\nPHP include_path: " . get_include_path() . "\n";

// List files in vendor/mongodb directory
$mongodbDir = __DIR__ . '/vendor/mongodb';
if (is_dir($mongodbDir)) {
    echo "\nFiles in $mongodbDir:\n";
    $files = scandir($mongodbDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file\n";
        }
    }
} else {
    echo "\n$mongodbDir directory not found!\n";
}

// Check Composer's autoload files
$composerAutoloadFiles = [
    __DIR__ . '/vendor/composer/autoload_real.php',
    __DIR__ . '/vendor/composer/autoload_classmap.php',
    __DIR__ . '/vendor/composer/autoload_namespaces.php',
    __DIR__ . '/vendor/composer/autoload_psr4.php',
];

echo "\nChecking Composer autoload files:\n";
foreach ($composerAutoloadFiles as $file) {
    echo "- " . basename($file) . ": " . (file_exists($file) ? 'Found' : 'NOT FOUND') . "\n";
}

// Try to manually include MongoDB classes
echo "\nTrying manual inclusion of MongoDB classes...\n";
$mongodbSrcDir = __DIR__ . '/vendor/mongodb/mongodb/src';
if (is_dir($mongodbSrcDir)) {
    echo "MongoDB source directory found at: $mongodbSrcDir\n";
    
    // Try including main MongoDB file
    $clientFile = $mongodbSrcDir . '/Client.php';
    if (file_exists($clientFile)) {
        echo "Found MongoDB Client class file.\n";
    } else {
        echo "ERROR: MongoDB Client class file not found!\n";
    }
} else {
    echo "ERROR: MongoDB source directory not found at: $mongodbSrcDir\n";
    echo "Run: composer require mongodb/mongodb\n";
}

// Test MongoDB connection if classes are available
if (class_exists('\MongoDB\Client')) {
    echo "\nTesting MongoDB connection...\n";
    try {
        $client = new \MongoDB\Client("mongodb://localhost:27017");
        echo "MongoDB connection successful!\n";
        
        // List databases
        echo "\nAvailable databases:\n";
        $databaseList = $client->listDatabases();
        foreach ($databaseList as $database) {
            echo "- " . $database['name'] . "\n";
        }
    } catch (Exception $e) {
        echo "MongoDB connection error: " . $e->getMessage() . "\n";
    }
}