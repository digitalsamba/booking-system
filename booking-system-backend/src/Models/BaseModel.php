<?php
/**
 * Base Model class
 * 
 * Provides common database functionality for all models
 */

namespace App\Models;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class BaseModel {
    protected $collection;
    protected $database;
    protected $client;
    
    /**
     * Constructor
     * 
     * @param string $collectionName Name of the MongoDB collection
     */
    public function __construct(string $collectionName) {
        // MongoDB connection parameters (consider moving these to config)
        $mongoHost = 'mongodb://localhost:27017';
        $mongoDb = 'booking_system';
        
        // Connect to MongoDB
        $this->client = new Client($mongoHost);
        $this->database = $this->client->selectDatabase($mongoDb);
        $this->collection = $this->database->selectCollection($collectionName);
    }
    
    /**
     * Format a MongoDB document to be returned as API response
     * 
     * @param array $document MongoDB document
     * @return array Formatted document
     */
    protected function formatDocument($document) {
        if (!$document) {
            return null;
        }
        
        // Convert MongoDB ObjectId to string
        if (isset($document['_id']) && $document['_id'] instanceof ObjectId) {
            $document['_id'] = (string)$document['_id'];
        }
        
        // Convert MongoDB dates to readable format
        $dateFields = ['created_at', 'updated_at', 'start_time', 'end_time'];
        foreach ($dateFields as $field) {
            if (isset($document[$field]) && $document[$field] instanceof UTCDateTime) {
                $document[$field] = $document[$field]->toDateTime()->format('Y-m-d H:i:s');
            }
        }
        
        return $document;
    }
    
    /**
     * Convert standard date string to MongoDB UTCDateTime
     * 
     * @param string|int $date Date string or timestamp
     * @return UTCDateTime
     */
    protected function toMongoDate($date) {
        if (is_numeric($date)) {
            return new UTCDateTime($date * 1000);
        }
        
        return new UTCDateTime(strtotime($date) * 1000);
    }
    
    /**
     * Format ObjectId to string
     * 
     * @param ObjectId|string $id
     * @return string
     */
    protected function formatId($id) {
        if ($id instanceof ObjectId) {
            return (string)$id;
        }
        return $id;
    }
    
    /**
     * Convert string ID to MongoDB ObjectId
     * 
     * @param string $id
     * @return ObjectId|null
     */
    protected function toObjectId($id) {
        try {
            return new ObjectId($id);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Generate timestamp fields for document creation or updates
     * 
     * @param bool $update Whether this is for an update operation
     * @return array Timestamp fields
     */
    protected function timestamps($update = false) {
        $now = new UTCDateTime(time() * 1000);
        
        if ($update) {
            return ['updated_at' => $now];
        }
        
        return [
            'created_at' => $now,
            'updated_at' => $now
        ];
    }
}