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
                error_log("No bearer token found in request");
                return null;
            }
            
            // Log token for debugging (only in development)
            error_log("Token received in getUserId: " . substr($token, 0, 20) . "...");
            
            $decoded = \App\Utils\JwtAuth::validateToken($token);
            
            if (!$decoded) {
                error_log("Token validation failed");
                return null;
            }
            
            // Debug the structure of the decoded token
            error_log("Decoded token data type: " . gettype($decoded->data));
            
            // Check for user ID in both possible locations - id or user_id
            if (!empty($decoded->data->id)) {
                return $decoded->data->id;
            } else if (!empty($decoded->data->user_id)) {
                return $decoded->data->user_id;
            } else {
                // Log what fields actually exist in the token
                $fields = [];
                foreach ($decoded->data as $key => $value) {
                    $fields[] = $key;
                }
                error_log("No user ID found in token. Available fields: " . implode(', ', $fields));
                return null;
            }
        } catch (\Exception $e) {
            error_log("Error extracting user ID from token: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get bearer token from Authorization header
     *
     * @return string|null Bearer token or null if not found
     */
    protected function getBearerToken(): ?string {
        // Use the JwtAuth utility method
        return \App\Utils\JwtAuth::getTokenFromHeader();
    }
}