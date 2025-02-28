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
        
        // Set response headers
        header('Content-Type: application/json');
        http_response_code($statusCode);
        
        // Output JSON data
        echo json_encode($data);
        exit;
    }
}