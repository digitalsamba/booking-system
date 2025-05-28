<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication and registration
 */

namespace App\Controllers;

use App\Models\UserModel;
use App\Utils\Response;
use Firebase\JWT\JWT;

class AuthController extends BaseController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    /**
     * Register a new user
     */
    public function register() {
        // Get JSON input data
        $data = $this->getJsonData();
        
        // Validate required fields
        $requiredFields = ['username', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                Response::json([
                    'error' => 'Missing required field',
                    'field' => $field
                ], 400);
                return;
            }
        }
        
        // Create the user model
        $userModel = new \App\Models\UserModel();
        
        // Pass all input data (including developer_key and team_id if present)
        $user = $userModel->register($data);
        
        if ($user) {
            Response::json([
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
        } else {
            Response::json([
                'error' => 'Registration failed',
                'details' => 'Username or email may already be in use'
            ], 400);
        }
    }
    
    /**
     * Login user
     */
    public function login() {
        // Get JSON input data
        $data = $this->getJsonData();
        
        // Validate required fields
        if (!isset($data['username'], $data['password'])) {
            Response::json(['error' => 'Missing required fields'], 400);
            return;
        }
        
        try {
            // Find user by username
            $user = $this->userModel->findByUsername($data['username']);
            
            if (!$user) {
                Response::json(['error' => 'User not found'], 404);
                return;
            }
            
            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                Response::json(['error' => 'Invalid password'], 401);
                return;
            }
            
            // Generate JWT token
            $jwt = $this->generateJwt($user);
            
            Response::json([
                'message' => 'Login successful',
                'token' => $jwt,
                'user' => [
                    'id' => isset($user['_id']) ? (string)$user['_id'] : '',
                    'username' => $user['username'],
                    'email' => $user['email'] ?? null,
                    'role' => $user['role'] ?? 'user',
                    'display_name' => $user['display_name'] ?? $user['username'],
                    'team_id' => $user['team_id'] ?? '',
                    'developer_key' => $user['developer_key'] ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log("Login error: " . $e->getMessage());
            Response::json(['error' => 'Server error during login: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Test endpoint
     */
    public function test() {
        Response::json([
            'message' => 'AuthController test endpoint is working!',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Generate a new test token
     * (Method name should match 'new-token' in hyphenated-url format)
     */
    public function newToken() {
        // Get JSON input data
        $data = $this->getJsonData();
        
        // Use provided user ID or a default
        $userId = $data['user_id'] ?? 'test-user-' . time();
        $username = $data['username'] ?? 'test-user';
        $role = $data['role'] ?? 'provider';
        
        // Create payload
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'role' => $role
        ];
        
        // Generate token
        $token = \App\Utils\JwtAuth::generateToken($payload);
        error_log("Generated new test token: " . substr($token, 0, 20) . "...");
        
        // Return response
        Response::json([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'role' => $role
            ]
        ]);
    }
    
    /**
     * Get the current user's profile
     */
    public function getProfile() {
        // Check for JWT token authentication
        $userId = $this->getUserId();
        
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        try {
            // Get user from database
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                Response::json(['error' => 'User not found'], 404);
                return;
            }
            
            // Return user profile data (excluding sensitive fields)
            Response::json([
                'id' => isset($user['_id']) ? (string)$user['_id'] : '',
                'username' => $user['username'],
                'email' => $user['email'] ?? null,
                'role' => $user['role'] ?? 'user',
                'display_name' => $user['display_name'] ?? $user['username'],
                'profile' => $user['profile'] ?? [],
                'team_id' => $user['team_id'] ?? '',
                'developer_key' => $user['developer_key'] ?? '',
                'created_at' => $user['created_at'] ?? null
            ]);
            
        } catch (\Exception $e) {
            error_log("Profile error: " . $e->getMessage());
            Response::json(['error' => 'Server error retrieving profile: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Update the current user's profile
     */
    public function updateProfile() {
        // Check for JWT token authentication
        $userId = $this->getUserId();
        
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get JSON input data
        // error_log("Raw input for updateProfile: " . file_get_contents('php://input')); // Removed Temp Debug
        $data = $this->getJsonData();
        
        try {
            // Update user in database
            $updated = $this->userModel->updateProfile($userId, $data); 
            // error_log("AuthController::updateProfile - updateProfile returned: " . ($updated ? 'true' : 'false')); // Removed Temp Debug
            
            if (!$updated) {
                Response::json(['error' => 'Failed to update profile'], 400);
                return;
            }
            
            // Get updated user data
            // error_log("AuthController::updateProfile - Calling findById for ID: {$userId}"); // Removed Temp Debug
            $user = $this->userModel->findById($userId);
            
            if (!$user) { // Added check for null user
                // error_log("AuthController::updateProfile - User found null after successful update for ID: {$userId}"); // Removed Temp Debug
                Response::json(['error' => 'Failed to retrieve profile after update'], 500);
                return;
            }
            
            // error_log("AuthController::updateProfile - findById returned user data. Preparing success response."); // Removed Temp Debug
            
            // Return updated profile in the structure expected by Response::json
            Response::json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => isset($user['_id']) ? (string)$user['_id'] : '',
                        'username' => $user['username'],
                        'email' => $user['email'] ?? null,
                        'role' => $user['role'] ?? 'user',
                        'display_name' => $user['display_name'] ?? $user['username'],
                        'profile' => $user['profile'] ?? [],
                        'team_id' => $user['team_id'] ?? '',
                        'developer_key' => $user['developer_key'] ?? '',
                        'updated_at' => time()
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            Response::json(['error' => 'Server error updating profile: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate JWT token
     */
    private function generateJwt($user) {
        $issuedAt = time();
        $expiration = $issuedAt + (defined('JWT_EXPIRY') ? JWT_EXPIRY : 3600);
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'data' => [
                'id' => isset($user['_id']) ? (string)$user['_id'] : '',
                'username' => $user['username'],
                'role' => $user['role'] ?? 'user'
            ]
        ];
        
        return \Firebase\JWT\JWT::encode(
            $payload, 
            defined('JWT_SECRET') ? JWT_SECRET : 'default_secret_change_this',
            'HS256'
        );
    }
}