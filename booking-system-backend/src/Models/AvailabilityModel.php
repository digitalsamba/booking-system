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
        try {
            if ($dateString instanceof UTCDateTime) {
                return $dateString;
            }
            
            if ($dateString instanceof \DateTime) {
                return new UTCDateTime($dateString->getTimestamp() * 1000);
            }
            
            if (is_string($dateString)) {
                // Try different date formats
                $formats = [
                    'Y-m-d H:i:s',
                    'Y-m-d\TH:i:s',
                    'Y-m-d H:i',
                    'Y-m-d'
                ];
                
                foreach ($formats as $format) {
                    $date = \DateTime::createFromFormat($format, $dateString);
                    if ($date !== false) {
                        return new UTCDateTime($date->getTimestamp() * 1000);
                    }
                }
                
                // If no format matched, try strtotime as a fallback
                $timestamp = strtotime($dateString);
                if ($timestamp !== false) {
                    return new UTCDateTime($timestamp * 1000);
                }
                
                throw new \InvalidArgumentException("Invalid date string: $dateString");
            }
            
            throw new \InvalidArgumentException("Invalid date format");
        } catch (\Exception $e) {
            error_log("Error converting date to MongoDB format: " . $e->getMessage() . "\nInput: " . print_r($dateString, true));
            throw $e;
        }
    }
    
    /**
     * Add availability slots
     *
     * @param string $userId The user ID
     * @param array $slots Array of availability slots
     * @return bool True if successful, false otherwise
     */
    public function addSlots(string $userId, array $slots): bool {
        try {
            $userObjectId = new ObjectId($userId);
            $documents = [];
            $duplicates = [];
            
            error_log("Starting to process " . count($slots) . " slots for user: " . $userId);
            
            foreach ($slots as $slot) {
                try {
                    // Validate slot data
                    if (!isset($slot['start_time']) || !isset($slot['end_time'])) {
                        error_log("Invalid slot data: " . json_encode($slot));
                        continue;
                    }
                    
                    // Convert dates to MongoDB format
                    $startTime = $this->toMongoDate($slot['start_time']);
                    $endTime = $this->toMongoDate($slot['end_time']);
                    
                    // Check for existing slot using exact timestamp comparison
                    $existingSlot = $this->collection->findOne([
                        'user_id' => $userObjectId,
                        'start_time' => $startTime,
                        'end_time' => $endTime
                    ]);
                    
                    if ($existingSlot) {
                        $duplicates[] = [
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time']
                        ];
                        continue;
                    }
                    
                    // Create document for each slot
                    $documents[] = [
                        'user_id' => $userObjectId,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_available' => true,
                        'created_at' => new UTCDateTime(time() * 1000),
                        'updated_at' => new UTCDateTime(time() * 1000)
                    ];
                } catch (\Exception $e) {
                    error_log("Error processing slot: " . $e->getMessage() . "\nSlot data: " . json_encode($slot));
                    continue;
                }
            }
            
            if (empty($documents)) {
                if (!empty($duplicates)) {
                    error_log("All slots were duplicates. Duplicate slots: " . json_encode($duplicates));
                    return false;
                }
                error_log("No valid slots to insert");
                return false;
            }
            
            error_log("Attempting to insert " . count($documents) . " slots");
            
            $result = $this->collection->insertMany($documents);
            
            if (!empty($duplicates)) {
                error_log("Some slots were duplicates. Created: {$result->getInsertedCount()}, Duplicates: " . count($duplicates));
            } else {
                error_log("Successfully inserted " . $result->getInsertedCount() . " slots");
            }
            
            return $result->getInsertedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error in addSlots: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get availability slots for a date range
     *
     * @param string $userId The user ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Array of slots
     */
    public function getSlots(string $userId, string $startDate, string $endDate): array {
        try {
            error_log("Getting slots for user: {$userId}, start: {$startDate}, end: {$endDate}");
            
            $userObjectId = new ObjectId($userId);
            $startDateTime = $this->toMongoDate($startDate . " 00:00:00");
            $endDateTime = $this->toMongoDate($endDate . " 23:59:59");
            
            error_log("Converted dates - start: " . $startDateTime->toDateTime()->format('Y-m-d H:i:s') . 
                     ", end: " . $endDateTime->toDateTime()->format('Y-m-d H:i:s'));
            
            $filter = [
                'user_id' => $userObjectId,
                'start_time' => [
                    '$gte' => $startDateTime,
                    '$lte' => $endDateTime
                ],
                'is_available' => true
            ];
            
            error_log("MongoDB filter: " . json_encode($filter));
            
            $options = [
                'sort' => ['start_time' => 1]
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $slots = [];
            
            foreach ($cursor as $document) {
                $slots[] = $this->formatSlotForApi($document);
            }
            
            error_log("Found " . count($slots) . " slots");
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
     * @return array Array of slots
     */
    public function getNextSevenDaysSlots(?string $userId): array {
        if (!$userId) {
            return [];
        }
        
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+7 days'));
        
        return $this->getSlots($userId, $startDate, $endDate);
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
        error_log("AVAILABILITY MODEL: Getting slot ID: {$id} for user ID: {$userId}");
        
        try {
            $slotId = new ObjectId($id);
            $userObjectId = new ObjectId($userId);
            
            $document = $this->collection->findOne([
                '_id' => $slotId,
                'user_id' => $userObjectId
            ]);
            
            if (!$document) {
                error_log("AVAILABILITY MODEL: Slot not found");
                return null;
            }
            
            $formattedSlot = $this->formatSlotForApi($document);
            error_log("AVAILABILITY MODEL: Found slot with availability: " . 
                (isset($formattedSlot['is_available']) ? ($formattedSlot['is_available'] ? 'true' : 'false') : 'unknown'));
            
            return $formattedSlot;
        } catch (\Exception $e) {
            error_log("AVAILABILITY MODEL ERROR: Exception while getting slot: " . $e->getMessage());
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
        error_log("AVAILABILITY MODEL: Updating slot ID: {$id} for user ID: {$userId} with data: " . json_encode($data));
        
        try {
            $slotId = new ObjectId($id);
            $userObjectId = new ObjectId($userId);
            
            $update = [];
            
            if (isset($data['start_time'])) {
                error_log("AVAILABILITY MODEL: Setting start_time: " . $data['start_time']);
                $update['start_time'] = $this->toMongoDate($data['start_time']);
            }
            
            if (isset($data['end_time'])) {
                error_log("AVAILABILITY MODEL: Setting end_time: " . $data['end_time']);
                $update['end_time'] = $this->toMongoDate($data['end_time']);
            }
            
            if (isset($data['is_available'])) {
                $isAvailable = (bool)$data['is_available'];
                error_log("AVAILABILITY MODEL: Setting is_available: " . ($isAvailable ? 'true' : 'false'));
                $update['is_available'] = $isAvailable;
            }
            
            if (empty($update)) {
                error_log("AVAILABILITY MODEL ERROR: No fields to update");
                return false;
            }
            
            $update['updated_at'] = new UTCDateTime(time() * 1000);
            
            // Log the filter and update operations
            error_log("AVAILABILITY MODEL: Filter: " . json_encode([
                '_id' => (string)$slotId,
                'user_id' => (string)$userObjectId
            ]));
            error_log("AVAILABILITY MODEL: Update: " . json_encode(['$set' => $update]));
            
            $result = $this->collection->updateOne(
                [
                    '_id' => $slotId,
                    'user_id' => $userObjectId
                ],
                ['$set' => $update]
            );
            
            $success = $result->getModifiedCount() > 0;
            error_log("AVAILABILITY MODEL: Update result - Modified count: {$result->getModifiedCount()}, Matched count: {$result->getMatchedCount()}, Success: " . ($success ? 'true' : 'false'));
            
            // If the update operation matched a document but didn't modify anything,
            // it might mean the document already had the specified field values
            if ($result->getMatchedCount() > 0 && $result->getModifiedCount() === 0) {
                error_log("AVAILABILITY MODEL: Document matched but no changes were made. This could mean the slot already had the specified values.");
                // We'll treat this as a success since the document exists and has the right values
                return true;
            }
            
            return $success;
        } catch (\Exception $e) {
            error_log("AVAILABILITY MODEL ERROR: Exception while updating slot: " . $e->getMessage());
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
        error_log("AVAILABILITY MODEL: Marking slot {$slotId} for provider {$providerId} as unavailable");
        
        try {
            $slotObjectId = new ObjectId($slotId);
            $providerObjectId = new ObjectId($providerId);
            
            // Log the current state before update
            $currentSlot = $this->collection->findOne([
                '_id' => $slotObjectId,
                'user_id' => $providerObjectId
            ]);
            
            if (!$currentSlot) {
                error_log("AVAILABILITY MODEL ERROR: Slot not found for marking unavailable");
                return false;
            }
            
            error_log("AVAILABILITY MODEL: Current slot state before marking unavailable: " . 
                (isset($currentSlot['is_available']) ? ($currentSlot['is_available'] ? 'available' : 'already unavailable') : 'availability unknown'));
            
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
            
            $success = $result->getModifiedCount() > 0;
            error_log("AVAILABILITY MODEL: markSlotUnavailable result - Modified count: {$result->getModifiedCount()}, Matched count: {$result->getMatchedCount()}, Success: " . ($success ? 'true' : 'false'));
            
            // If the update operation matched a document but didn't modify anything,
            // it might mean the document was already marked as unavailable
            if ($result->getMatchedCount() > 0 && $result->getModifiedCount() === 0) {
                error_log("AVAILABILITY MODEL: Slot matched but no changes were made. The slot might already be unavailable.");
                // Verify the current state
                $verifySlot = $this->collection->findOne([
                    '_id' => $slotObjectId,
                    'user_id' => $providerObjectId
                ]);
                
                if ($verifySlot && isset($verifySlot['is_available']) && $verifySlot['is_available'] === false) {
                    error_log("AVAILABILITY MODEL: Verified that slot is indeed marked as unavailable");
                    return true;
                }
            }
            
            return $success;
        } catch (\Exception $e) {
            error_log("AVAILABILITY MODEL ERROR: Exception while marking slot unavailable: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all availability slots for a user
     *
     * @param string $userId User ID
     * @return bool True if successful, false otherwise
     */
    public function deleteAllSlots(string $userId): bool {
        try {
            $userObjectId = new ObjectId($userId);
            
            $result = $this->collection->deleteMany([
                'user_id' => $userObjectId
            ]);
            
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error deleting all availability slots: " . $e->getMessage());
            return false;
        }
    }
}