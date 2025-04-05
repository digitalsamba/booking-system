<?php

namespace App\Utils\Email;

/**
 * Configuration class for email services
 * 
 * This class loads and manages configuration from .env file
 */
class EmailConfig {
    /** @var array Configuration values */
    private static $config = [];
    
    /** @var bool Whether configuration has been loaded */
    private static $loaded = false;
    
    /**
     * Load configuration from .env file
     *
     * @return void
     */
    public static function load(): void {
        if (self::$loaded) {
            return;
        }
        
        // Find the .env file in project root
        $envFile = dirname(dirname(dirname(__DIR__))) . '/.env';
        
        if (!file_exists($envFile)) {
            error_log("EmailConfig: .env file not found at: " . $envFile);
            self::$loaded = true;
            return;
        }
        
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
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || 
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$config[$name] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get a configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     */
    public static function get(string $key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Get all configuration values
     *
     * @return array All configuration values
     */
    public static function all(): array {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config;
    }
} 