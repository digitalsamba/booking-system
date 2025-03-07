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
    // Add these properties
    protected $userId = null;
    protected $userRole = null;
    
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
     * Require authentication for an endpoint
     * Sets userId and userRole if authenticated
     *
     * @return bool True if authenticated
     */
    protected function requireAuth(): bool {
        $this->userId = $this->getUserId();
        if (!$this->userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return false;
        }
        
        // Get role from token
        $token = $this->getBearerToken();
        if ($token) {
            $decoded = \App\Utils\JwtAuth::validateToken($token);
            if ($decoded) {
                $this->userRole = $decoded->data->role ?? 'user';
            }
        }
        
        return true;
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
    
    /**
     * Validate date format
     * 
     * @param string $date Date string in YYYY-MM-DD format
     * @return bool True if valid
     */
    protected function validateDateFormat($date): bool {
        if (!is_string($date)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate time format
     * 
     * @param string $time Time string in HH:MM format
     * @return bool True if valid
     */
    protected function validateTimeFormat($time): bool {
        if (!is_string($time)) {
            return false;
        }
        $t = \DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }
    
    /**
     * Get ID from URL path
     *
     * @return string|null
     */
    protected function getIdFromPath(): ?string {
        $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
        return $pathParts[1] ?? null;
    }
}