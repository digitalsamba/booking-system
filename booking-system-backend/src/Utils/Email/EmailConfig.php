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
            error_log("EMAIL DEBUG: Config already loaded, skipping reload");
            return;
        }
        
        error_log("EMAIL DEBUG: Loading environment variables");

        // First try to load directly from environment variables (which bootstrap may have set)
        // This ensures consistency with other parts of the application
        $envKeys = [
            
            'EMAIL_PROVIDER',
            'EMAIL_FROM',
            'EMAIL_FROM_NAME',
            'SMTP_HOST',
            'SMTP_PORT',
            'SMTP_USERNAME',
            'SMTP_PASSWORD',
            'SMTP_ENCRYPTION',
            'SENDGRID_API_KEY',
            'APP_ENV'
        ];
        
        $loadedFromEnv = false;
        foreach ($envKeys as $key) {
            $value = getenv($key);
            if ($value !== false) {
                self::$config[$key] = $value;
                $loadedFromEnv = true;
                
                // Log the key (but protect sensitive values)
                $logValue = $key === 'SENDGRID_API_KEY' ? (empty($value) ? 'empty' : 'length:'.strlen($value)) : $value;
                error_log("EMAIL DEBUG: Loaded from ENV variable '$key' = '$logValue'");
            }
        }
        
        // If we found environment variables, no need to read the .env file
        if ($loadedFromEnv) {
            error_log("EMAIL DEBUG: Successfully loaded config from environment variables");
            // Still check the .env file for any additional variables not in our predefined list
        }
        
        // Fallback to reading the .env file
        $envFile = dirname(dirname(dirname(__DIR__))) . '/.env';
        error_log("EMAIL DEBUG: Also checking .env file: " . $envFile);
        
        if (!file_exists($envFile)) {
            error_log("EMAIL DEBUG: .env file not found at: " . $envFile);
            self::$loaded = true;
            return;
        }
        
        error_log("EMAIL DEBUG: Found .env file");
        
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
                
                // Only overwrite if not already set from environment
                if (!isset(self::$config[$name])) {
                    self::$config[$name] = $value;
                    
                    // Log loading of email-related config
                    if (strpos($name, 'EMAIL') !== false || strpos($name, 'SENDGRID') !== false || 
                        strpos($name, 'SMTP') !== false || strpos($name, 'MAIL') !== false) {
                        $logValue = $name === 'SENDGRID_API_KEY' ? (empty($value) ? 'empty' : 'length:'.strlen($value)) : $value;
                        error_log("EMAIL DEBUG: Loaded from .env file '$name' = '$logValue'");
                    }
                }
            }
        }
        
        // Log key email config values for debugging
        $emailProvider = self::$config['EMAIL_PROVIDER'] ?? 'not set';
        error_log("EMAIL DEBUG: EMAIL_PROVIDER = '$emailProvider'");
        
        $apiKey = isset(self::$config['SENDGRID_API_KEY']) ? 'present ('.strlen(self::$config['SENDGRID_API_KEY']).' chars)' : 'not set';
        error_log("EMAIL DEBUG: SENDGRID_API_KEY is $apiKey");
        
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
        
        $value = self::$config[$key] ?? $default;
        
        // Log important email config retrievals
        if (strpos($key, 'EMAIL') !== false || strpos($key, 'SENDGRID') !== false || 
            strpos($key, 'SMTP') !== false || strpos($key, 'MAIL') !== false) {
            $logValue = $key === 'SENDGRID_API_KEY' ? (empty($value) ? 'empty' : 'length:'.strlen($value)) : $value;
            error_log("EMAIL DEBUG: Retrieved config '$key' = '$logValue'");
        }
        
        return $value;
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