<?php
/**
 * Environment variable loader
 * Loads .env files and provides helper function for accessing env vars
 */

/**
 * Load environment variables from .env file
 * 
 * @param string $path Path to .env file
 * @return void
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Get environment variable with fallback
 * 
 * @param string $key Environment variable key
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key) ?: $default;
    
    // Convert string booleans
    if (is_string($value)) {
        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }
    }
    
    return $value;
}