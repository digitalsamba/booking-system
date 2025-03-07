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
    public function register(): void {
        // Get JSON input data
        $data = $this->getJsonData();
        
        // Validate required fields
        $requiredFields = ['username', 'email', 'password'];
        $missing = $this->validateRequiredFields($data, $requiredFields);
        if ($missing) {
            $this->error("Missing required fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Pass all input data (including developer_key and team_id if present)
        $user = $this->userModel->register($data);
        
        if ($user) {
            $this->success([
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
        } else {
            $this->error('Registration failed', 400, ['details' => 'Username or email may already be in use']);
        }
    }
    
    /**
     * Handle user login
     */
    public function login(): void {
        // Get request data
        $data = $this->getJsonData(); // Use helper instead of direct json_decode
        
        // Validate required fields
        $missing = $this->validateRequiredFields($data, ['username', 'password']);
        if ($missing) {
            $this->error("Missing required fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Log login attempt for debugging
        $this->debug("Login attempt", $data['username']);
        
        // Find user by username
        $user = $this->userModel->findByUsername($data['username']);
        
        // Debug the user data
        $this->debug("User data found", $user ? 'User found' : 'No user found');
        
        // Verify user and password
        if (!$user || !password_verify($data['password'], $user['password'])) {
            $this->error('Invalid username or password', 401);
            return;
        }
        
        // Make sure user has a valid ID
        if (empty($user['id'])) {
            $this->debug("User found but has no ID", $user);
            $this->error('Authentication error: User ID not found', 500);
            return;
        }
        
        // Generate JWT token
        $tokenData = [
            'id' => $user['id'],  // Use 'id' to match what getUserId() looks for
            'username' => $user['username'],
            'role' => $user['role'] ?? 'user'
        ];
        
        $this->debug("Generating token with data", $tokenData);
        $token = \App\Utils\JwtAuth::generateToken($tokenData);
        
        // Return response with token and user info
        $this->success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'] ?? '',
                'role' => $user['role'] ?? 'user'
            ]
        ]);
    }
    
    /**
     * Test endpoint
     */
    public function test(): void {
        $this->success([
            'message' => 'AuthController test endpoint is working!',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Generate a new test token
     * (Method name should match 'new-token' in hyphenated-url format)
     */
    public function newToken(): void {
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
        $this->debug("Generated new test token", substr($token, 0, 20) . "...");
        
        // Return response
        $this->success([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'role' => $role
            ]
        ]);
    }
}