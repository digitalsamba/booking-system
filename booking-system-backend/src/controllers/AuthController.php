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
     * Handle user login
     */
    public function login() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['username']) || !isset($data['password'])) {
            Response::json(['error' => 'Username and password are required'], 400);
            return;
        }
        
        // Log login attempt for debugging
        error_log("Login attempt for username: " . $data['username']);
        
        // Find user by username
        $userModel = new \App\Models\UserModel();
        $user = $userModel->findByUsername($data['username']);
        
        // Debug the user data
        error_log("User data found: " . ($user ? json_encode($user) : 'No user found'));
        
        // Verify user and password
        if (!$user || !password_verify($data['password'], $user['password'])) {
            Response::json(['error' => 'Invalid username or password'], 401);
            return;
        }
        
        // Make sure user has a valid ID
        if (empty($user['id'])) {
            error_log("ERROR: User found but has no ID: " . json_encode($user));
            Response::json(['error' => 'Authentication error: User ID not found'], 500);
            return;
        }
        
        // Generate JWT token
        $tokenData = [
            'id' => $user['id'],  // Use 'id' to match what getUserId() looks for
            'username' => $user['username'],
            'role' => $user['role'] ?? 'user'
        ];
        
        error_log("Generating token with data: " . json_encode($tokenData));
        $token = \App\Utils\JwtAuth::generateToken($tokenData);
        
        // Return response with token and user info
        $response = [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'] ?? '',
                'role' => $user['role'] ?? 'user'
            ]
        ];
        
        Response::json($response);
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