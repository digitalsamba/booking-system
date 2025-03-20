<?php
/**
 * Database Utility
 * 
 * Handles MongoDB connection and provides database access
 * MODIFIED VERSION: Using in-memory storage for development/testing
 */

namespace App\Utils;

/**
 * Fallback Collection class that simulates MongoDB operations
 * for development/testing purposes when MongoDB is not available
 */
class MockMongoCollection {
    private $name;
    private $data = [];
    private static $collections = [];
    
    public function __construct($name) {
        $this->name = $name;
        if (!isset(self::$collections[$name])) {
            self::$collections[$name] = [];
        }
        $this->data = &self::$collections[$name];
    }
    
    public function insertOne($document) {
        // Generate a unique ID if not provided
        if (!isset($document['_id'])) {
            $document['_id'] = new \MongoDB\BSON\ObjectId();
        }
        
        $this->data[(string)$document['_id']] = $document;
        
        // Return a result object that simulates MongoDB's result
        return new class($document['_id']) {
            private $id;
            public function __construct($id) {
                $this->id = $id;
            }
            public function getInsertedId() {
                return $this->id;
            }
        };
    }
    
    public function insertMany($documents) {
        $ids = [];
        foreach ($documents as $document) {
            $result = $this->insertOne($document);
            $ids[] = $result->getInsertedId();
        }
        
        // Return a result object that simulates MongoDB's result
        return new class($ids) {
            private $ids;
            public function __construct($ids) {
                $this->ids = $ids;
            }
            public function getInsertedIds() {
                return $this->ids;
            }
        };
    }
    
    public function findOne($filter = []) {
        foreach ($this->data as $document) {
            $match = true;
            foreach ($filter as $key => $value) {
                if (!isset($document[$key]) || $document[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $document;
            }
        }
        return null;
    }
    
    public function find($filter = [], $options = []) {
        $results = [];
        
        foreach ($this->data as $document) {
            $match = true;
            foreach ($filter as $key => $value) {
                if (!isset($document[$key]) || $document[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[] = $document;
            }
        }
        
        // Sort if needed
        if (isset($options['sort'])) {
            usort($results, function($a, $b) use ($options) {
                foreach ($options['sort'] as $field => $order) {
                    if (!isset($a[$field]) && !isset($b[$field])) {
                        continue;
                    }
                    if (!isset($a[$field])) return $order * -1;
                    if (!isset($b[$field])) return $order;
                    
                    if ($a[$field] == $b[$field]) {
                        continue;
                    }
                    
                    return ($a[$field] < $b[$field]) ? ($order * -1) : $order;
                }
                return 0;
            });
        }
        
        // Handle limit
        if (isset($options['limit']) && $options['limit'] > 0) {
            $results = array_slice($results, 0, $options['limit']);
        }
        
        return $results;
    }
    
    public function updateOne($filter, $update, $options = []) {
        $found = false;
        $matchedCount = 0;
        $modifiedCount = 0;
        $upsertedId = null;
        
        foreach ($this->data as &$document) {
            $match = true;
            foreach ($filter as $key => $value) {
                if (!isset($document[$key]) || $document[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                $matchedCount++;
                $found = true;
                
                if (isset($update['$set'])) {
                    foreach ($update['$set'] as $key => $value) {
                        if (!isset($document[$key]) || $document[$key] != $value) {
                            $document[$key] = $value;
                            $modifiedCount++;
                        }
                    }
                }
                
                break; // Only update the first match
            }
        }
        
        // Handle upsert
        if (!$found && isset($options['upsert']) && $options['upsert']) {
            $newDocument = [];
            
            // Add filter criteria to new document
            foreach ($filter as $key => $value) {
                $newDocument[$key] = $value;
            }
            
            // Add update fields to new document
            if (isset($update['$set'])) {
                foreach ($update['$set'] as $key => $value) {
                    $newDocument[$key] = $value;
                }
            }
            
            // Insert the new document
            $result = $this->insertOne($newDocument);
            $upsertedId = $result->getInsertedId();
        }
        
        // Return a result object that simulates MongoDB's result
        return new class($matchedCount, $modifiedCount, $upsertedId) {
            private $matchedCount;
            private $modifiedCount;
            private $upsertedId;
            
            public function __construct($matchedCount, $modifiedCount, $upsertedId) {
                $this->matchedCount = $matchedCount;
                $this->modifiedCount = $modifiedCount;
                $this->upsertedId = $upsertedId;
            }
            
            public function getMatchedCount() {
                return $this->matchedCount;
            }
            
            public function getModifiedCount() {
                return $this->modifiedCount;
            }
            
            public function getUpsertedId() {
                return $this->upsertedId;
            }
        };
    }
    
    public function deleteMany($filter = []) {
        $count = 0;
        
        foreach ($this->data as $id => $document) {
            $match = true;
            foreach ($filter as $key => $value) {
                if (!isset($document[$key]) || $document[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                unset($this->data[$id]);
                $count++;
            }
        }
        
        // Return a result object that simulates MongoDB's result
        return new class($count) {
            private $count;
            
            public function __construct($count) {
                $this->count = $count;
            }
            
            public function getDeletedCount() {
                return $this->count;
            }
        };
    }
    
    public function deleteOne($filter) {
        foreach ($this->data as $id => $document) {
            $match = true;
            foreach ($filter as $key => $value) {
                if (!isset($document[$key]) || $document[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                unset($this->data[$id]);
                return new class(1) {
                    private $count;
                    
                    public function __construct($count) {
                        $this->count = $count;
                    }
                    
                    public function getDeletedCount() {
                        return $this->count;
                    }
                };
            }
        }
        
        // No document matched
        return new class(0) {
            private $count;
            
            public function __construct($count) {
                $this->count = $count;
            }
            
            public function getDeletedCount() {
                return $this->count;
            }
        };
    }
}

/**
 * Fallback Database class for when MongoDB is not available
 */
class MockMongoDatabase {
    private $collections = [];
    
    public function selectCollection($name) {
        if (!isset($this->collections[$name])) {
            $this->collections[$name] = new MockMongoCollection($name);
        }
        return $this->collections[$name];
    }
}

/**
 * Database utility class for MongoDB connections
 * with fallback to in-memory storage for development/testing
 */
class Database {
    /**
     * @var mixed MongoDB database instance or fallback
     */
    private static $database;
    
    /**
     * Get database instance (singleton pattern)
     *
     * @return mixed The database instance
     */
    public static function getDatabase() {
        if (self::$database === null) {
            try {
                // Check if MongoDB\Client class exists and attempt connection
                if (class_exists('\MongoDB\Client')) {
                    self::$database = self::connectMongo();
                    error_log("Using real MongoDB connection");
                } else {
                    throw new \Exception("MongoDB extension not available");
                }
            } catch (\Exception $e) {
                // Fallback to in-memory database
                error_log("WARNING: Using in-memory database fallback: " . $e->getMessage());
                self::$database = new MockMongoDatabase();
            }
        }
        
        return self::$database;
    }
    
    /**
     * Connect to MongoDB and return database instance
     *
     * @return \MongoDB\Database The MongoDB database instance
     */
    private static function connectMongo() {
        // Load database configuration
        $configPath = defined('CONFIG_PATH') ? CONFIG_PATH : dirname(dirname(__DIR__)) . '/config';
        $dbConfig = require $configPath . '/database.php';
        $config = $dbConfig['mongodb'];
        
        // Build connection string
        $connectionString = "mongodb://";
        if (!empty($config['username']) && !empty($config['password'])) {
            $connectionString .= $config['username'] . ":" . $config['password'] . "@";
        }
        $connectionString .= $config['host'] . ":" . $config['port'];
        
        try {
            // Create MongoDB client and select database
            $client = new \MongoDB\Client($connectionString);
            $database = $client->selectDatabase($config['database']);
            
            // Test connection with a simple command
            $database->command(['ping' => 1]);
            
            return $database;
        } catch (\Exception $e) {
            throw new \Exception("MongoDB connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get a collection from the database
     *
     * @param string $collectionName The collection name
     * @return mixed The database collection
     */
    public static function getCollection(string $collectionName) {
        return self::getDatabase()->selectCollection($collectionName);
    }
}