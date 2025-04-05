<?php
/**
 * Test script for environment variables using dotenv
 */

// Load bootstrap
require __DIR__ . '/bootstrap.php';

echo "Environment Variables Test (using dotenv)\n";
echo "-------------------------------------\n\n";

$testVars = [
    'EMAIL_PROVIDER',
    'EMAIL_FROM',
    'EMAIL_FROM_NAME',
    'SENDGRID_API_KEY',
    'JWT_SECRET',
    'DB_HOST'
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

echo "\nTest complete.\n"; 