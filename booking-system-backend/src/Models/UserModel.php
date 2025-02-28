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