<?php
/**
 * Create Test User Script
 */

require_once __DIR__ . '/bootstrap.php';

try {
    $mongoHost = 'mongodb://localhost:27017';
    $mongoDb = 'booking_system';
    
    echo "Connecting to MongoDB at {$mongoHost}...\n";
    
    $client = new MongoDB\Client($mongoHost);
    $database = $client->selectDatabase($mongoDb);
    $usersCollection = $database->selectCollection('users');
    
    echo "Connected to MongoDB\n\n";
    
    // Check if test user already exists
    $existingUser = $usersCollection->findOne(['username' => 'test@example.com']);
    
    if ($existingUser) {
        echo "Test user already exists with ID: " . (string)$existingUser['_id'] . "\n";
    } else {
        // Create test user
        $testUser = [
            'username' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'display_name' => 'Test User',
            'team_id' => 'test-team-id',
            'developer_key' => 'test-developer-key-12345',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $result = $usersCollection->insertOne($testUser);
        echo "Created test user with ID: " . (string)$result->getInsertedId() . "\n";
        echo "Username: test@example.com\n";
        echo "Password: password123\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
