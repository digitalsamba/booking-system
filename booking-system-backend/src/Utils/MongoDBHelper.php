<?php
/**
 * MongoDB Helper Utility
 * 
 * Helps with MongoDB document handling
 */

namespace App\Utils;

use MongoDB\BSON\ObjectId;

class MongoDBHelper {
    /**
     * Convert MongoDB document to standard array
     *
     * @param mixed $document MongoDB document
     * @return array Standard array
     */
    public static function toArray($document) {
        if (!$document) {
            return null;
        }
        
        $array = (array)$document;
        
        // Convert ObjectId to string
        if (isset($array['_id']) && $array['_id'] instanceof ObjectId) {
            $array['id'] = (string)$array['_id'];
        }
        
        return $array;
    }
    
    /**
     * Convert string to ObjectId
     *
     * @param string $id String ID
     * @return ObjectId|null MongoDB ObjectId
     */
    public static function toObjectId($id) {
        try {
            return new ObjectId($id);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Format a MongoDB document for API output
     *
     * @param mixed $document MongoDB document
     * @return array Formatted document
     */
    public static function formatForApi($document) {
        if (!$document) {
            return null;
        }
        
        $array = self::toArray($document);
        
        // Remove sensitive fields
        unset($array['password']);
        
        // Convert MongoDB specific types to standard types
        foreach ($array as $key => $value) {
            if ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $array[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            }
        }
        
        return $array;
    }
}