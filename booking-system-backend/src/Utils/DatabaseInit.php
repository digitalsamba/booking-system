<?php
/**
 * Database Initialization Utility
 * 
 * Creates necessary indexes for optimal performance
 */

namespace App\Utils;

// No need to import Database since it's in the same namespace
// Remove this line: use App\Utils\Database;

class DatabaseInit {
    /**
     * Initialize database with required indexes
     *
     * @return void
     */
    public static function initializeIndexes(): void {
        // Users collection indexes
        $usersCollection = Database::getCollection('users');
        $usersCollection->createIndex(['username' => 1], ['unique' => true]);
        $usersCollection->createIndex(['email' => 1], ['unique' => true]);
        
        // Availability collection indexes
        $availabilityCollection = Database::getCollection('availability');
        $availabilityCollection->createIndex(['user_id' => 1]);
        $availabilityCollection->createIndex(['date' => 1]);
        $availabilityCollection->createIndex(['start_time' => 1]);
        $availabilityCollection->createIndex(['is_available' => 1]);
        $availabilityCollection->createIndex(['user_id' => 1, 'date' => 1]);
        
        // Bookings collection indexes
        $bookingsCollection = Database::getCollection('bookings');
        $bookingsCollection->createIndex(['user_id' => 1]);
        $bookingsCollection->createIndex(['visitor_email' => 1]);
        $bookingsCollection->createIndex(['start_time' => 1]);
        $bookingsCollection->createIndex(['status' => 1]);
        $bookingsCollection->createIndex(['user_id' => 1, 'start_time' => 1]);
    }
}