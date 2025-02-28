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
     * Get Bearer token from Authorization header
     *
     * @return string|null The token or null if not found
     */
    protected function getBearerToken(): ?string {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}