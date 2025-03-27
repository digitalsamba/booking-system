<?php

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\JwtAuth;

class DebugController extends BaseController {
    
    /**
     * Debug endpoint to test JWT token generation and validation
     */
    public function jwtTest() {
        // Generate a test JWT token
        $payload = [
            'user_id' => 'test-user-123',
            'username' => 'debugger',
            'role' => 'admin'
        ];
        
        $token = JwtAuth::generateToken($payload);
        
        // Try to validate the token we just generated
        $validationResult = JwtAuth::validateToken($token);
        
        // Return the results
        Response::json([
            'success' => true,
            'jwt_secret_defined' => defined('JWT_SECRET'),
            'jwt_secret_preview' => defined('JWT_SECRET') ? substr(JWT_SECRET, 0, 3) . '...' : null,
            'token' => $token,
            'validation_result' => [
                'success' => $validationResult ? true : false,
                'payload' => $validationResult ? $validationResult->data : null
            ]
        ]);
    }
}