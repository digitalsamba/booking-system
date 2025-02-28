<?php
// filepath: /c:/Users/ffxxr/Documents/DS/projects/booking-system/booking-system-backend/src/utils/ResponseDebugger.php

namespace App\Utils;

/**
 * Response Debugger Utility
 * 
 * Helps debug API responses and errors
 */
class ResponseDebugger {
    /**
     * Log detailed error information
     *
     * @param string $message Error message
     * @param mixed $context Additional context data
     * @return void
     */
    public static function logError(string $message, $context = null): void {
        $debug = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];
        
        if ($context !== null) {
            $debug['context'] = $context;
        }
        
        error_log(json_encode($debug));
    }
    
    /**
     * Create a detailed error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param mixed $details Additional error details
     * @return void
     */
    public static function errorResponse(string $message, int $code = 500, $details = null): void {
        $response = [
            'error' => true,
            'message' => $message,
            'code' => $code,
        ];
        
        if ($details !== null && defined('DEBUG_MODE') && DEBUG_MODE === true) {
            $response['details'] = $details;
        }
        
        self::logError($message, $details);
        
        Response::json($response, $code);
    }
}