<?php
/**
 * Availability Model
 * 
 * Handles database operations for availability slots
 */

namespace App\Models;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class AvailabilityModel extends BaseModel {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('availability');
    }
    
    /**
     * Convert a date string to MongoDB UTCDateTime
     *
     * @param string|\DateTime|UTCDateTime $dateString Date string or object
     * @return UTCDateTime MongoDB date object
     * @throws \InvalidArgumentException If date is invalid
     */
    protected function toMongoDate($dateString): UTCDateTime {
        if ($dateString instanceof UTCDateTime) {
            return $dateString;
        }
        
        if ($dateString instanceof \DateTime) {
            return new UTCDateTime($dateString->getTimestamp() * 1000);
        }
        
        if (is_string($dateString)) {
            $timestamp = strtotime($dateString);
            if ($timestamp === false) {
                throw new \InvalidArgumentException("Invalid date string: $dateString");
            }
            return new UTCDateTime($timestamp * 1000);
        }
        
        throw new \InvalidArgumentException("Invalid date format");
    }
    
    /**
     * Add availability slots
     *
     * @param string $userId The user ID
     * @param array $slots Array of availability slots
     * @return bool True if successful, false otherwise
     */
    public function addSlots(string $userId, array $slots): bool {
        $userObjectId = new ObjectId($userId);
        
        $documents = [];
        
        foreach ($slots as $slot) {
            try {
                // Create document for each slot
                $documents[] = [
                    'user_id' => $userObjectId,
                    'start_time' => $this->toMongoDate($slot['start_time']),
                    'end_time' => $this->toMongoDate($slot['end_time']),
                    'is_available' => true,
                    'created_at' => new UTCDateTime(time() * 1000),
                    'updated_at' => new UTCDateTime(time() * 1000)
                ];
            } catch (\Exception $e) {
                error_log("Error processing slot: " . $e->getMessage());
                // Continue with other slots
            }
        }
        
        if (empty($documents)) {
            return false;
        }
        
        try {
            $result = $this->collection->insertMany($documents);
            return $result->getInsertedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error adding availability slots: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get availability slots for a date range
     *
     * @param string $userId The user ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param bool $requireAuth Whether to require authentication
     * @return array Array of slots
     */
    public function getSlots(string $userId, string $startDate, string $endDate, bool $requireAuth = false): array {
        try {
            $userObjectId = new ObjectId($userId);
            $startDateTime = $this->toMongoDate($startDate . " 00:00:00");
            $endDateTime = $this->toMongoDate($endDate . " 23:59:59");
            
            $filter = [
                'user_id' => $userObjectId,
                'start_time' => [
                    '$gte' => $startDateTime,
                    '$lte' => $endDateTime
                ]
            ];
            
            // Only show available slots if this is a public query
            if (!$requireAuth) {
                $filter['is_available'] = true;
            }
            
            $options = [
                'sort' => ['start_time' => 1]
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $slots = [];
            
            foreach ($cursor as $document) {
                $slots[] = $this->formatSlotForApi($document);
            }
            
            return $slots;
        } catch (\Exception $e) {
            error_log("Error getting availability slots: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get next seven days availability slots
     *
     * @param string|null $userId The user ID (nullable)
     * @param bool $requireAuth Whether authentication is required
     * @return array Array of slots
     */
    public function getNextSevenDaysSlots(?string $userId, bool $requireAuth = false): array {
        if (!$userId) {
            return [];
        }
        
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+7 days'));
        
        return $this->getSlots($userId, $startDate, $endDate, $requireAuth);
    }
    
    /**
     * Delete a slot
     *
     * @param string $id Slot ID
     * @param string $userId User ID for security check
     * @return bool True if successful, false otherwise
     */
    public function deleteSlot(string $id, string $userId): bool {
        try {
            $slotId = new ObjectId($id);
            $userObjectId = new ObjectId($userId);
            
            $result = $this->collection->deleteOne([
                '_id' => $slotId,
                'user_id' => $userObjectId
            ]);
            
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error deleting slot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a slot by ID
     *
     * @param string $id Slot ID
     * @param string $userId User ID for security check
     * @return array|null The slot or null if not found
     */
    public function getSlot(string $id, string $userId): ?array {
        try {
            $slotId = new ObjectId($id);
            $userObjectId = new ObjectId($userId);
            
            $document = $this->collection->findOne([
                '_id' => $slotId,
                'user_id' => $userObjectId
            ]);
            
            if (!$document) {
                return null;
            }
            
            return $this->formatSlotForApi($document);
        } catch (\Exception $e) {
            error_log("Error getting slot: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update a slot
     *
     * @param string $id Slot ID
     * @param array $data Update data
     * @param string $userId User ID for security check
     * @return bool True if successful, false otherwise
     */
    public function updateSlot(string $id, array $data, string $userId): bool {
        try {
            $slotId = new ObjectId($id);
            $userObjectId = new ObjectId($userId);
            
            $update = [];
            
            if (isset($data['start_time'])) {
                $update['start_time'] = $this->toMongoDate($data['start_time']);
            }
            
            if (isset($data['end_time'])) {
                $update['end_time'] = $this->toMongoDate($data['end_time']);
            }
            
            if (isset($data['is_available'])) {
                $update['is_available'] = (bool)$data['is_available'];
            }
            
            if (empty($update)) {
                return false;
            }
            
            $update['updated_at'] = new UTCDateTime(time() * 1000);
            
            $result = $this->collection->updateOne(
                [
                    '_id' => $slotId,
                    'user_id' => $userObjectId
                ],
                ['$set' => $update]
            );
            
            // If the document was matched but not modified, consider it a success
            // (it may already have the desired values)
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error updating slot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Format MongoDB date to string
     *
     * @param UTCDateTime|null $date MongoDB date
     * @param string $format PHP date format
     * @return string|null Formatted date string
     */
    private function formatDate(?UTCDateTime $date, string $format = 'Y-m-d H:i:s'): ?string {
        if (!$date) {
            return null;
        }
        
        return $date->toDateTime()->format($format);
    }
    
    /**
     * Format slot document for API response
     *
     * @param array $document MongoDB document
     * @return array Formatted slot
     */
    private function formatSlotForApi($document): array {
        return [
            'id' => (string)$document['_id'],
            'user_id' => (string)$document['user_id'],
            'start_time' => $this->formatDate($document['start_time']),
            'end_time' => $this->formatDate($document['end_time']),
            'is_available' => $document['is_available'] ?? true,
            'created_at' => $this->formatDate($document['created_at'] ?? null),
            'updated_at' => $this->formatDate($document['updated_at'] ?? null)
        ];
    }
    
    /**
     * Mark a slot as unavailable
     *
     * @param string $slotId Slot ID
     * @param string $providerId Provider ID
     * @return bool Success flag
     */
    public function markSlotUnavailable(string $slotId, string $providerId): bool {
        try {
            $slotObjectId = new ObjectId($slotId);
            $providerObjectId = new ObjectId($providerId);
            
            $result = $this->collection->updateOne(
                [
                    '_id' => $slotObjectId,
                    'user_id' => $providerObjectId
                ],
                [
                    '$set' => [
                        'is_available' => false,
                        'updated_at' => new UTCDateTime(time() * 1000)
                    ]
                ]
            );
            
            // If the document was matched but not modified, consider it a success
            // (it may already be marked as unavailable)
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error marking slot unavailable: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get public availability for a provider
     * This is a non-authenticated endpoint that should be used for public queries
     *
     * @param string $providerId Provider/User ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Array of available slots
     */
    public function getPublicAvailability(string $providerId, string $startDate, string $endDate): array {
        // For public endpoints, we only return available slots
        return $this->getSlots($providerId, $startDate, $endDate, false);
    }
}