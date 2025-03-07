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
     * @return array|null Formatted document
     */
    protected function formatDocument($document) {
        if (!$document) {
            return null;
        }
        
        $result = (array)$document;
        
        // Convert MongoDB ObjectId to string
        if (isset($result['_id']) && $result['_id'] instanceof ObjectId) {
            $result['id'] = (string)$result['_id'];
            unset($result['_id']);
        }
        
        // Convert MongoDB dates to readable format
        $dateFields = ['created_at', 'updated_at', 'start_time', 'end_time'];
        foreach ($dateFields as $field) {
            if (isset($result[$field]) && $result[$field] instanceof UTCDateTime) {
                $result[$field] = $result[$field]->toDateTime()->format('Y-m-d H:i:s');
            }
        }
        
        return $result;
    }
    
    /**
     * Convert standard date string to MongoDB UTCDateTime
     * 
     * @param string|int|\DateTime|UTCDateTime $date Date string, timestamp or object
     * @return UTCDateTime|null MongoDB date object or null on failure
     */
    protected function toMongoDate($date) {
        try {
            if ($date instanceof UTCDateTime) {
                return $date;
            }
            
            if ($date instanceof \DateTime) {
                return new UTCDateTime($date->getTimestamp() * 1000);
            }
            
            if (is_numeric($date)) {
                return new UTCDateTime((int)$date * 1000);
            }
            
            if (is_string($date)) {
                $timestamp = strtotime($date);
                if ($timestamp === false) {
                    return null;
                }
                return new UTCDateTime($timestamp * 1000);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Error converting to MongoDB date: " . $e->getMessage());
            return null;
        }
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
            error_log("Error converting to ObjectId: " . $e->getMessage());
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
    
    /**
     * Find a document by ID
     * 
     * @param string $id Document ID
     * @return array|null Document data or null if not found
     */
    public function getById($id) {
        try {
            $objectId = $this->toObjectId($id);
            if (!$objectId) {
                return null;
            }
            
            $document = $this->collection->findOne(['_id' => $objectId]);
            return $document ? $this->formatDocument($document) : null;
        } catch (\Exception $e) {
            error_log("Error finding document by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update a document
     * 
     * @param string $id Document ID
     * @param array $data Update data
     * @return bool Success flag
     */
    public function update($id, array $data) {
        try {
            $objectId = $this->toObjectId($id);
            if (!$objectId) {
                return false;
            }
            
            // Add update timestamp
            $data = array_merge($data, $this->timestamps(true));
            
            $result = $this->collection->updateOne(
                ['_id' => $objectId],
                ['$set' => $data]
            );
            
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error updating document: " . $e->getMessage());
            return false;
        }
    }
}