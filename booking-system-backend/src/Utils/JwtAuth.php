<?php
/**
 * JWT Authentication Utility
 * 
 * Handles JWT token generation and validation
 */

namespace App\Utils;

// Place the require statement AFTER the namespace declaration
require_once __DIR__ . '/../../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth {
    // Store the secret as a static property that's initialized once
    private static $secret = null;
    
    /**
     * Get the JWT secret, ensuring it's loaded properly
     */
    private static function getSecret() {
        // If the secret is already loaded, return it
        if (self::$secret !== null) {
            return self::$secret;
        }
        
        // Try to get from the defined constant
        if (defined('JWT_SECRET')) {
            self::$secret = JWT_SECRET;
            error_log("Secret loaded from JWT_SECRET constant: " . substr(self::$secret, 0, 3) . "...");
        } else {
            // Fallback secret for development only - NOT SECURE FOR PRODUCTION!
            self::$secret = 'default-jwt-secret-for-development-only';
            error_log("WARNING: Using hardcoded fallback JWT secret - not secure for production!");
        }
        
        return self::$secret;
    }
    
    /**
     * Generate a JWT token
     *
     * @param array $payload The token payload (user data)
     * @param int $expiry Expiry time in seconds (default: use JWT_EXPIRY or 24 hours)
     * @return string The JWT token
     */
    public static function generateToken($payload, $expiry = null) {
        if (!defined('JWT_SECRET')) {
            error_log('JWT_SECRET is not defined in config.php');
            throw new \Exception('JWT configuration error');
        }
        
        $issuedAt = time();
        $expiration = $issuedAt + ($expiry ?? (defined('JWT_EXPIRY') ? JWT_EXPIRY : 86400)); // Default 24 hours
        
        $tokenPayload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'data' => $payload
        ];
        
        $token = JWT::encode($tokenPayload, JWT_SECRET, 'HS256');
        error_log("Token generated for user: " . ($payload['user_id'] ?? 'unknown'));
        return $token;
    }
    
    /**
     * Validate a JWT token
     *
     * @param string $token The JWT token
     * @return object|false The decoded token if valid, false otherwise
     */
    public static function validateToken($token) {
        try {
            error_log("Validating token: " . substr($token, 0, 20) . "...");
            
            if (!defined('JWT_SECRET')) {
                error_log('JWT_SECRET is not defined when validating token');
                return false;
            }
            
            // For debugging
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            error_log("Token validated successfully for user: " . 
                ($decoded->data->user_id ?? 'unknown'));
            
            return $decoded;
        } catch (\Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract token from Authorization header
     * 
     * @return string|null The token or null if not found
     */
    public static function getTokenFromHeader() {
        error_log("Attempting to extract token from request headers");
        
        // Try apache_request_headers first
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $authHeader = $value;
                    error_log("Found Authorization header: " . substr($authHeader, 0, 30) . "...");
                    break;
                }
            }
        }
        
        // If not found, try $_SERVER['HTTP_AUTHORIZATION']
        if (!isset($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        // If still not found, check for REDIRECT_HTTP_AUTHORIZATION (Apache with mod_rewrite)
        if (!isset($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        // If no authorization header found
        if (!isset($authHeader)) {
            error_log("No Authorization header found in request");
            return null;
        }
        
        // Extract the token
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            error_log("Bearer token extracted successfully");
            return $token;
        }
        
        error_log("Authorization header found but no Bearer token");
        return null;
    }    

}