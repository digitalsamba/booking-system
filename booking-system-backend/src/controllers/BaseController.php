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
            $this->debug("Empty request body received");
            return [];
        }
        
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->debug("JSON decode error", json_last_error_msg());
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
            $this->error('Authentication required', 401);
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
                $this->debug("No bearer token found in request");
                return null;
            }
            
            // Log token for debugging (only in development)
            $this->debug("Token received", substr($token, 0, 20) . "...");
            
            $decoded = \App\Utils\JwtAuth::validateToken($token);
            
            if (!$decoded) {
                $this->debug("Token validation failed");
                return null;
            }
            
            // Debug the structure of the decoded token
            $this->debug("Decoded token data type", gettype($decoded->data));
            
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
                $this->debug("No user ID found in token. Available fields", implode(', ', $fields));
                return null;
            }
        } catch (\Exception $e) {
            $this->debug("Error extracting user ID from token", $e->getMessage());
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
     * Send error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array $details Additional error details
     * @return void
     */
    protected function error(string $message, int $code = 400, array $details = []): void {
        $response = ['error' => $message];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        Response::json($response, $code);
    }

    /**
     * Get query parameter with optional default value
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value if parameter not found
     * @return mixed Parameter value or default
     */
    protected function getQueryParam(string $name, $default = null) {
        return $_GET[$name] ?? $default;
    }

    /**
     * Validate required fields in data array
     * 
     * @param array $data Data to validate
     * @param array $requiredFields Required field names
     * @return array|null Array of missing fields or null if all fields present
     */
    protected function validateRequiredFields(array $data, array $requiredFields): ?array {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return empty($missing) ? null : $missing;
    }

    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param int $code HTTP status code
     * @return void
     */
    protected function success($data = [], int $code = 200): void {
        $response = ['success' => true];
        
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
        
        Response::json($response, $code);
    }

    /**
     * Get path parts from URL
     * 
     * @return array Array of URL path segments
     */
    protected function getPathParts(): array {
        return explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
    }

    /**
     * Get resource ID from URL path (usually second segment)
     * 
     * @param int $position Position of the ID in path segments (default: 1)
     * @return string|null Resource ID or null if not found
     */
    protected function getIdFromPath(int $position = 1): ?string {
        $parts = $this->getPathParts();
        return $parts[$position] ?? null;
    }

    /**
     * Check if the current request matches a given HTTP method
     *
     * @param string $method HTTP method to check (GET, POST, etc)
     * @return bool True if request method matches
     */
    protected function isMethod(string $method): bool {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }

    /**
     * Check if current request is a GET request
     *
     * @return bool
     */
    protected function isGet(): bool {
        return $this->isMethod('GET');
    }

    /**
     * Check if current request is a POST request
     *
     * @return bool
     */
    protected function isPost(): bool {
        return $this->isMethod('POST');
    }

    /**
     * Check if current request is a PUT request
     *
     * @return bool
     */
    protected function isPut(): bool {
        return $this->isMethod('PUT');
    }

    /**
     * Check if current request is a DELETE request
     *
     * @return bool
     */
    protected function isDelete(): bool {
        return $this->isMethod('DELETE');
    }

    /**
     * Log debug message (only if DEBUG is enabled)
     * 
     * @param string $message Message to log
     * @param mixed $data Optional data to include
     * @return void
     */
    protected function debug(string $message, $data = null): void {
        if (!defined('DEBUG') || !DEBUG) {
            return;
        }
        
        $logMessage = "[DEBUG] " . get_class($this) . ": " . $message;
        
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $logMessage .= " - " . json_encode($data);
            } else {
                $logMessage .= " - " . $data;
            }
        }
        
        error_log($logMessage);
    }
}