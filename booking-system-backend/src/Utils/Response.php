<?php
/**
 * HTTP Response Utility
 */

namespace App\Utils;

class Response {
    /**
     * Send a JSON response
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function json($data, int $statusCode = 200): void {
        // Set CORS headers
        if (defined('ALLOW_ORIGIN')) {
            header('Access-Control-Allow-Origin: ' . ALLOW_ORIGIN);
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        
        if (defined('ALLOW_METHODS')) {
            header('Access-Control-Allow-Methods: ' . ALLOW_METHODS);
        } else {
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        }
        
        if (defined('ALLOW_HEADERS')) {
            header('Access-Control-Allow-Headers: ' . ALLOW_HEADERS);
        } else {
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }
        
        // Ensure consistent response structure for successful responses
        if ($statusCode >= 200 && $statusCode < 300) {
            // Check if $data is already structured with success, message, and data fields
            if (!is_array($data) || 
                !(isset($data['success']) && isset($data['data']))) {
                // If not properly structured, create the expected structure
                $responseData = [
                    'success' => true,
                    'data' => $data ?? null
                ];
                
                // If there's a message in the original data, preserve it
                if (is_array($data) && isset($data['message'])) {
                    $responseData['message'] = $data['message'];
                }
                
                $data = $responseData;
            }
        } else {
            // For error responses, ensure we have a standardized format
            if (!is_array($data) || !isset($data['error'])) {
                $data = [
                    'success' => false,
                    'error' => is_string($data) ? $data : 'An error occurred',
                    'data' => null
                ];
            }
        }
        
        // Set response headers
        header('Content-Type: application/json');
        http_response_code($statusCode);
        
        // Output JSON data
        echo json_encode($data);
        exit;
    }
}