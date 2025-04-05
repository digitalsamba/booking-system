<?php
/**
 * Test script for environment variables
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Environment Variables Test\n";
echo "------------------------\n\n";

// Get current directory
$dir = __DIR__;
echo "Current directory: $dir\n\n";

// Check if .env file exists
$envFile = $dir . '/.env';
echo ".env file exists: " . (file_exists($envFile) ? 'Yes' : 'No') . "\n";
if (file_exists($envFile)) {
    echo "File size: " . filesize($envFile) . " bytes\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($envFile)), -4) . "\n";
    
    // Read the first few lines of the file
    echo "\nFirst 10 lines of .env file:\n";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        // Only show variable names, not values for security
        $line = $lines[$i];
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            echo "$name=[VALUE HIDDEN]\n";
        } else {
            echo "$line\n";
        }
    }
}

echo "\nTesting getenv() function:\n";
$testVars = [
    'EMAIL_PROVIDER',
    'EMAIL_FROM',
    'EMAIL_FROM_NAME',
    'SENDGRID_API_KEY',
    'JWT_SECRET',  // For comparison with a known variable
    'DB_HOST'      // For comparison with a known variable
];

foreach ($testVars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        if ($var === 'SENDGRID_API_KEY') {
            echo "$var: " . (empty($value) ? "EMPTY" : "Found (length: " . strlen($value) . ")") . "\n";
        } else {
            // Show first 10 chars of value for non-sensitive variables
            $displayValue = substr($value, 0, 10) . (strlen($value) > 10 ? '...' : '');
            echo "$var: $displayValue\n";
        }
    } else {
        echo "$var: NOT FOUND\n";
    }
}

// Try to manually load .env file
echo "\nTrying to manually load .env file:\n";
$content = file_get_contents($envFile);
$lines = explode("\n", $content);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) {
        continue; // Skip empty lines and comments
    }
    if (strpos($line, '=') !== false) {
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if ($name === 'SENDGRID_API_KEY') {
            echo "Found $name in file with length " . strlen($value) . "\n";
        }
    }
}

// Check for packages that might help with .env loading
echo "\nChecking for dotenv package:\n";
$composerJson = $dir . '/composer.json';
if (file_exists($composerJson)) {
    $composer = json_decode(file_get_contents($composerJson), true);
    if (isset($composer['require']['vlucas/phpdotenv'])) {
        echo "vlucas/phpdotenv is installed (version: " . $composer['require']['vlucas/phpdotenv'] . ")\n";
    } else {
        echo "vlucas/phpdotenv is NOT installed\n";
        echo "Recommend installing: composer require vlucas/phpdotenv\n";
    }
} else {
    echo "composer.json not found\n";
}

echo "\nTest complete.\n"; 