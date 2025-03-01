<?php
/**
 * User Model
 * 
 * Handles user-related database operations
 */

namespace App\Models;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class UserModel extends BaseModel {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('users');
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return string|false The inserted ID or false on failure
     */
    public function create(array $data) {
        try {
            // Add timestamps
            $now = new UTCDateTime(time() * 1000);
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            
            $result = $this->collection->insertOne($data);
            
            if ($result->getInsertedCount() > 0) {
                return (string)$result->getInsertedId();
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a username already exists
     * 
     * @param string $username Username to check
     * @return bool True if username exists
     */
    public function usernameExists(string $username): bool {
        try {
            $count = $this->collection->countDocuments(['username' => $username]);
            return $count > 0;
        } catch (\Exception $e) {
            error_log("Error checking if username exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an email already exists
     * 
     * @param string $email Email to check
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool {
        try {
            $count = $this->collection->countDocuments(['email' => $email]);
            return $count > 0;
        } catch (\Exception $e) {
            error_log("Error checking if email exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a new user
     * 
     * @param array $userData User data
     * @return array|bool New user data or false if registration failed
     */
    public function register(array $userData): array|bool {
        try {
            // Validate required fields
            if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
                return false;
            }
            
            // Check if username or email already exists
            if ($this->usernameExists($userData['username']) || $this->emailExists($userData['email'])) {
                return false;
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Prepare user data
            $newUser = [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => $hashedPassword,
                'role' => 'user',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add Digital Samba credentials if provided
            if (!empty($userData['developer_key'])) {
                $newUser['developer_key'] = $userData['developer_key'];
            }
            
            if (!empty($userData['team_id'])) {
                $newUser['team_id'] = $userData['team_id'];
            }
            
            // Insert into database
            $result = $this->collection->insertOne($newUser);
            
            if ($result->getInsertedCount() > 0) {
                // Get the inserted user
                $newUser['id'] = (string)$result->getInsertedId();
                unset($newUser['password']); // Remove password from returned data
                return $newUser;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error registering user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find a user by username
     * 
     * @param string $username
     * @return array|null User data or null if not found
     */
    public function findByUsername(string $username) {
        try {
            $user = $this->collection->findOne(['username' => $username]);
            return $user ? $this->formatDocument($user) : null;
        } catch (\Exception $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email) {
        try {
            $user = $this->collection->findOne(['email' => $email]);
            return $user ? $this->formatDocument($user) : null;
        } catch (\Exception $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a user by ID
     * 
     * @param string $id User ID
     * @return array|null User data or null if not found
     */
    public function findById(string $id) {
        try {
            $user = $this->collection->findOne(['_id' => new ObjectId($id)]);
            return $user ? $this->formatDocument($user) : null;
        } catch (\Exception $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update a user
     * 
     * @param string $id User ID
     * @param array $data Data to update
     * @return bool Success flag
     */
    public function update(string $id, array $data) {
        try {
            // Add update timestamp
            $data['updated_at'] = new UTCDateTime(time() * 1000);
            
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $data]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user password
     * 
     * @param string $id User ID
     * @param string $hashedPassword New hashed password
     * @return bool Success flag
     */
    public function updatePassword(string $id, string $hashedPassword) {
        return $this->update($id, ['password' => $hashedPassword]);
    }
    
    /**
     * Convert MongoDB document to array
     * 
     * @param array $document MongoDB document
     * @return array Formatted array
     */
    public function toArray($document = null) {
        return $this->formatDocument($document);
    }
}