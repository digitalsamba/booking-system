<?php
/**
 * Direct test for environment variables
 * 
 * This script loads the .env file directly without depending on getenv()
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

echo "Direct Environment Variables Test\n";
echo "-------------------------------\n\n";

// Open and parse .env file manually
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo ".env file exists (size: " . filesize($envFile) . " bytes)\n\n";
    
    $content = file_get_contents($envFile);
    $lines = explode("\n", $content);
    
    echo "Reading environment variables directly:\n";
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue; // Skip empty lines and comments
        }
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || 
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Display info about the variable (safe display for sensitive data)
            if ($name === 'SENDGRID_API_KEY' || $name === 'JWT_SECRET' || 
                $name === 'DB_PASS' || strpos($name, 'PASSWORD') !== false) {
                echo "$name: [SENSITIVE - length: " . strlen($value) . "]\n";
            } else {
                echo "$name: $value\n";
            }
            
            // Set as environment variable using putenv()
            putenv("$name=$value");
        }
    }
    
    echo "\nLoaded variables to environment using putenv()\n";
    
    // Load through Dotenv
    echo "\nLoading through Dotenv library:\n";
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        echo "Dotenv loaded successfully\n";
        
        // Test if $_ENV was populated
        echo "\nChecking \$_ENV array:\n";
        foreach (['EMAIL_PROVIDER', 'SENDGRID_API_KEY'] as $key) {
            echo "$key in \$_ENV: " . (isset($_ENV[$key]) ? "Yes" : "No") . "\n";
        }
        
        // Test if putenv() worked
        echo "\nChecking getenv() after Dotenv:\n";
        foreach (['EMAIL_PROVIDER', 'SENDGRID_API_KEY'] as $key) {
            $value = getenv($key);
            echo "$key via getenv(): " . ($value !== false ? "Found" : "Not found") . "\n";
        }
        
    } catch (Exception $e) {
        echo "Error loading Dotenv: " . $e->getMessage() . "\n";
    }
    
} else {
    echo ".env file not found!\n";
}

echo "\nTest complete.\n"; 