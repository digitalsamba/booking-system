<?php
/**
 * Base Controller
 * 
 * Provides common functionality for all controllers
 */

namespace App\Controllers;

use App\Utils\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

abstract class BaseController {
    /**
     * Send a JSON response
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function jsonResponse($data, int $statusCode = 200): void {
        Response::json($data, $statusCode);
    }

    /**
     * Get JSON data from request body
     *
     * @return array The parsed JSON data
     */
    protected function getJsonData(): array {
        $json = file_get_contents('php://input');
        
        if (empty($json)) {
            error_log("WARNING: Empty request body in " . get_class($this));
            return [];
        }
        
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg() . " in " . get_class($this));
            return [];
        }
        
        return $data;
    }
    
    /**
     * Ensure user is authenticated
     * 
     * @return void
     * @throws Exception if user is not authenticated
     */
    protected function requireAuth(): void {
        $userId = $this->getUserId();
        
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            exit;
        }
    }
    
    /**
     * Get authenticated user ID from JWT token
     *
     * @return string|null User ID or null if not authenticated
     */
    protected function getUserId(): ?string {
        try {
            $token = $this->getBearerToken();
            
            if (!$token) {
                return null;
            }
            
            $secret = defined('JWT_SECRET') ? JWT_SECRET : 'default_secret_change_this';
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            
            return $decoded->data->id ?? null;
        } catch (Exception $e) {
            error_log("JWT validation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all HTTP headers (polyfill for environments where getallheaders() is not available)
     *
     * @return array The headers
     */
    protected function getAllHeaders(): array {
        // Use native function if available
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        // Otherwise, create the headers from $_SERVER
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } elseif ($name === 'CONTENT_TYPE' || $name === 'CONTENT_LENGTH') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                $headers[$name] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Get Bearer token from Authorization header
     *
     * @return string|null The token or null if not found
     */
    protected function getBearerToken(): ?string {
        // Try multiple methods to get Authorization header
        
        // 1. Check headers from getAllHeaders function
        $headers = $this->getAllHeaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $auth = $value;
                error_log("Found Authorization header in getAllHeaders: " . substr($auth, 0, 20) . "...");
                if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
                    error_log("Extracted token from getAllHeaders: " . substr($matches[1], 0, 20) . "...");
                    return $matches[1];
                }
            }
        }
        
        // 2. Direct check in $_SERVER['HTTP_AUTHORIZATION']
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            error_log("Found HTTP_AUTHORIZATION in _SERVER: " . substr($auth, 0, 20) . "...");
            if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
                error_log("Extracted token from HTTP_AUTHORIZATION: " . substr($matches[1], 0, 20) . "...");
                return $matches[1];
            }
        }
        
        // 3. Check REDIRECT_HTTP_AUTHORIZATION (for Apache with mod_rewrite)
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            error_log("Found REDIRECT_HTTP_AUTHORIZATION in _SERVER: " . substr($auth, 0, 20) . "...");
            if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
                error_log("Extracted token from REDIRECT_HTTP_AUTHORIZATION: " . substr($matches[1], 0, 20) . "...");
                return $matches[1];
            }
        }
        
        error_log("No Authorization header found with Bearer token");
        return null;
    }
}