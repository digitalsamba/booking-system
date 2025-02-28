<?php
/**
 * Database Utility
 * 
 * Handles MongoDB connection and provides database access
 */

namespace App\Utils;

// Import the MongoDB classes if they exist
if (class_exists('\MongoDB\Client')) {
    class_alias('\MongoDB\Client', 'App\Utils\MongoClientAlias');
    class_alias('\MongoDB\Database', 'App\Utils\MongoDatabaseAlias'); 
    class_alias('\MongoDB\Collection', 'App\Utils\MongoCollectionAlias');
} else {
    // Fallback class definitions when MongoDB extension is not available
    class MongoClientAlias {}
    class MongoDatabaseAlias {}
    class MongoCollectionAlias {}
    
    error_log("WARNING: MongoDB PHP library not found. Using fallback classes.");
}

/**
 * Database utility class for MongoDB connections
 */
class Database {
    /**
     * @var MongoDatabaseAlias MongoDB database instance
     */
    private static $database;
    
    /**
     * Get MongoDB database instance (singleton pattern)
     *
     * @return MongoDatabaseAlias The MongoDB database instance
     * @throws \Exception if MongoDB is not available
     */
    public static function getDatabase() {
        // Check if MongoDB\Client class exists
        if (!class_exists('\MongoDB\Client')) {
            throw new \Exception("MongoDB PHP library not available. Run 'composer require mongodb/mongodb'");
        }
        
        if (self::$database === null) {
            self::$database = self::connect();
        }
        
        return self::$database;
    }
    
    /**
     * Connect to MongoDB and return database instance
     *
     * @return MongoDatabaseAlias The MongoDB database instance
     */
    private static function connect() {
        // Load database configuration using global constant or fallback path
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
            
            return $database;
        } catch (\Exception $e) {
            throw new \Exception("MongoDB connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get a collection from the database
     *
     * @param string $collectionName The collection name
     * @return \MongoDB\Collection The MongoDB collection
     */
    public static function getCollection(string $collectionName) {
        return self::getDatabase()->selectCollection($collectionName);
    }
}