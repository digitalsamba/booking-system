<?php
/**
 * Test script to verify the SendGrid API key issue
 */

// Include necessary files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Utils/Email/EmailConfig.php';

use App\Utils\Email\EmailConfig;

// Start by loading config
echo "Starting SendGrid API key test...\n";

// First load config from EmailConfig
EmailConfig::load();
$configKey = EmailConfig::get('SENDGRID_API_KEY');
echo "API key from EmailConfig: " . (empty($configKey) ? "Empty" : "Length: " . strlen($configKey)) . "\n";
echo "Key starts with: " . substr($configKey, 0, 10) . "...\n";

// Now read the key directly from the file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo ".env file found\n";
    $content = file_get_contents($envFile);
    $matches = [];
    if (preg_match('/SENDGRID_API_KEY\s*=\s*[\'"]*([^\'"\n]+)[\'"]*/i', $content, $matches)) {
        $directKey = trim($matches[1]);
        echo "API key read directly from file: Length: " . strlen($directKey) . "\n";
        echo "Key starts with: " . substr($directKey, 0, 10) . "...\n";
        
        // Check if the keys match
        if ($configKey === $directKey) {
            echo "RESULT: Keys match exactly!\n";
        } else {
            echo "RESULT: Keys DO NOT match!\n";
            
            // Check if one is truncated version of the other
            if (substr($directKey, 0, strlen($configKey)) === $configKey) {
                echo "The EmailConfig key appears to be a truncated version of the direct key\n";
            } else if (substr($configKey, 0, strlen($directKey)) === $directKey) {
                echo "The direct key appears to be a truncated version of the EmailConfig key\n";
            } else {
                echo "The keys are completely different\n";
            }
        }
    } else {
        echo "Could not find SENDGRID_API_KEY in .env file\n";
    }
} else {
    echo ".env file not found\n";
}

// Now test the getenv method
$envKey = getenv('SENDGRID_API_KEY');
if ($envKey !== false) {
    echo "API key from getenv(): Length: " . strlen($envKey) . "\n";
    echo "Key starts with: " . substr($envKey, 0, 10) . "...\n";
    
    // Compare with direct key
    if (isset($directKey) && $envKey !== $directKey) {
        echo "The getenv() key is different from the direct key\n";
        if (substr($directKey, 0, strlen($envKey)) === $envKey) {
            echo "The getenv() key appears to be a truncated version of the direct key\n";
        }
    }
} else {
    echo "API key not found in environment variables\n";
}

// Print the actual key from .env so we can visually inspect it
if (isset($directKey)) {
    echo "\nActual API key from .env (DO NOT SHARE): " . $directKey . "\n";
}

echo "\nTest complete.\n";
