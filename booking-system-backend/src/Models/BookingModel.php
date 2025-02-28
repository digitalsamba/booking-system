<?php
/**
 * BookingModel class
 * Handles all booking operations
 */

namespace App\Models;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class BookingModel extends BaseModel {
    private $availabilityModel;
    
    public function __construct() {
        parent::__construct('bookings');
        $this->availabilityModel = new AvailabilityModel();
        error_log("BOOKING MODEL: Initialized with collection 'bookings'");
    }
    
    /**
     * Check if a time slot is available
     *
     * @param string $userId User ID
     * @param string $startTime Start time (Y-m-d H:i:s)
     * @param string $endTime End time (Y-m-d H:i:s)
     * @return bool True if slot is available
     */
    public function isSlotAvailable($userId, $startTime, $endTime) {
        $start = new \DateTime($startTime);
        $end = new \DateTime($endTime);
        
        $startMongoDate = new UTCDateTime($start->getTimestamp() * 1000);
        $endMongoDate = new UTCDateTime($end->getTimestamp() * 1000);
        
        // Check if there's an availability slot for this time
        $availabilityModel = new AvailabilityModel();
        $dateStr = $start->format('Y-m-d');
        $startTimeStr = $start->format('H:i:s');
        $endTimeStr = $end->format('H:i:s');
        
        $date = new \DateTime($dateStr);
        $mongoDate = new UTCDateTime($date->getTimestamp() * 1000);
        
        $availabilitySlot = $availabilityModel->collection->findOne([
            'user_id' => $userId,
            'date' => $mongoDate,
            'start_time' => $startTimeStr,
            'end_time' => $endTimeStr,
            'is_available' => true
        ]);
        
        if (!$availabilitySlot) {
            return false;
        }
        
        // Check if there's an existing booking for this time
        $existingBooking = $this->collection->findOne([
            'user_id' => $userId,
            'start_time' => [
                '$lt' => $endMongoDate
            ],
            'end_time' => [
                '$gt' => $startMongoDate
            ],
            'status' => [
                '$ne' => 'cancelled'
            ]
        ]);
        
        return $existingBooking === null;
    }
    
    /**
     * Create a new booking
     * 
     * @param array $data Booking data
     * @return array|bool The created booking or false on failure
     */
    public function create(array $data) {
        error_log("BOOKING MODEL: Creating booking with data: " . json_encode($data));
        
        try {
            // Validate required data
            if (empty($data['provider_id']) || empty($data['slot_id']) || empty($data['customer'])) {
                error_log("BOOKING MODEL ERROR: Missing required data fields");
                return false;
            }
            
            // Get the slot to make sure it exists and is available
            error_log("BOOKING MODEL: Checking slot ID: " . $data['slot_id'] . " for provider ID: " . $data['provider_id']);
            $slot = $this->availabilityModel->getSlot($data['slot_id'], $data['provider_id']);
            
            if (!$slot) {
                error_log("BOOKING MODEL ERROR: Slot not found");
                return false;
            }
            
            error_log("BOOKING MODEL: Found slot: " . json_encode($slot));
            
            if (empty($slot['is_available'])) {
                error_log("BOOKING MODEL ERROR: Slot is not available. is_available=" . 
                    ($slot['is_available'] ? 'true' : 'false'));
                return false;
            }
            
            // Create the booking document
            $bookingId = new ObjectId();
            
            $booking = [
                '_id' => $bookingId,
                'provider_id' => new ObjectId($data['provider_id']),
                'slot_id' => new ObjectId($data['slot_id']),
                'customer' => $data['customer'],
                'notes' => $data['notes'] ?? '',
                'status' => 'confirmed',
                'created_at' => new UTCDateTime(time() * 1000),
                'updated_at' => new UTCDateTime(time() * 1000)
            ];
            
            // Add start and end times if available in the slot
            if (!empty($slot['start_time'])) {
                $booking['start_time'] = new UTCDateTime(strtotime($slot['start_time']) * 1000);
            }
            
            if (!empty($slot['end_time'])) {
                $booking['end_time'] = new UTCDateTime(strtotime($slot['end_time']) * 1000);
            }
            
            error_log("BOOKING MODEL: Inserting booking document into database");
            
            // Insert booking
            $result = $this->collection->insertOne($booking);
            
            if ($result->getInsertedCount() > 0) {
                error_log("BOOKING MODEL: Booking inserted with ID: " . (string)$bookingId);
                
                // Mark the slot as unavailable
                error_log("BOOKING MODEL: Marking slot as unavailable using updateSlot method");
                $updateResult = $this->availabilityModel->updateSlot(
                    $data['slot_id'],
                    ['is_available' => false],
                    $data['provider_id']
                );
                error_log("BOOKING MODEL: Slot marked as unavailable. Update result: " . json_encode($updateResult));
                
                return $booking;
            }
            
            error_log("BOOKING MODEL ERROR: Failed to insert booking");
            return false;
        } catch (\Exception $e) {
            error_log("BOOKING MODEL ERROR: Exception occurred while creating booking: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user bookings
     *
     * @param string $userId The user ID
     * @return array The user's bookings
     */
    public function getUserBookings(string $userId): array {
        $userObjectId = $this->toObjectId($userId);
        if (!$userObjectId) {
            return [];
        }
        
        // Get user's bookings
        $bookings = $this->collection->find([
            'user_id' => $userObjectId
        ], [
            'sort' => ['start_time' => 1]
        ]);
        
        $results = [];
        foreach ($bookings as $booking) {
            $results[] = $this->toArray($booking);
        }
        
        return $results;
    }
    
    /**
     * Get bookings for a user
     *
     * @param string $userId User ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Bookings
     */
    public function getUserBookingsByDate($userId, $startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end->setTime(23, 59, 59);
        
        $startMongoDate = new UTCDateTime($start->getTimestamp() * 1000);
        $endMongoDate = new UTCDateTime($end->getTimestamp() * 1000);
        
        $filter = [
            'user_id' => $userId,
            'start_time' => [
                '$gte' => $startMongoDate,
                '$lte' => $endMongoDate
            ]
        ];
        
        $cursor = $this->collection->find($filter, [
            'sort' => ['start_time' => 1]
        ]);
        
        $bookings = [];
        
        foreach ($cursor as $booking) {
            $bookings[] = MongoDBHelper::formatForApi($booking);
        }
        
        return $bookings;
    }
    
    /**
     * Get bookings for a time period
     *
     * @param string $startTime Start time string (Y-m-d H:i:s)
     * @param string $endTime End time string (Y-m-d H:i:s)
     * @param string $userId Optional user ID filter
     * @return array The bookings in the specified period
     */
    public function getBookingsInPeriod(string $startTime, string $endTime, ?string $userId = null): array {
        // Convert dates to MongoDB format
        $startDateTime = new DateTime($startTime);
        $endDateTime = new DateTime($endTime);
        
        // Prepare query
        $query = [
            'start_time' => [
                '$gte' => $this->toMongoDate($startDateTime),
                '$lte' => $this->toMongoDate($endDateTime),
            ],
        ];
        
        // Add user filter if provided
        if ($userId) {
            $userObjectId = $this->toObjectId($userId);
            if ($userObjectId) {
                $query['user_id'] = $userObjectId;
            }
        }
        
        // Get bookings
        $bookings = $this->collection->find($query, ['sort' => ['start_time' => 1]]);
        
        $results = [];
        foreach ($bookings as $booking) {
            $results[] = $this->toArray($booking);
        }
        
        return $results;
    }
    
    /**
     * Check if a time slot is already booked
     *
     * @param string $startTime Start time
     * @param string $endTime End time
     * @param string $providerId Provider ID
     * @param string $excludeBookingId Optional booking ID to exclude (for updates)
     * @return bool True if slot is booked, false otherwise
     */
    public function isSlotBooked(string $startTime, string $endTime, string $providerId, ?string $excludeBookingId = null): bool {
        $startMongoDate = $this->toMongoDate($startTime);
        $endMongoDate = $this->toMongoDate($endTime);
        $providerObjectId = $this->toObjectId($providerId);
        
        $query = [
            'provider_id' => $providerObjectId,
            '$or' => [
                // Booking starts during requested slot
                [
                    'start_time' => [
                        '$gte' => $startMongoDate,
                        '$lt' => $endMongoDate
                    ]
                ],
                // Booking ends during requested slot
                [
                    'end_time' => [
                        '$gt' => $startMongoDate,
                        '$lte' => $endMongoDate
                    ]
                ],
                // Booking contains requested slot
                [
                    'start_time' => ['$lte' => $startMongoDate],
                    'end_time' => ['$gte' => $endMongoDate]
                ]
            ]
        ];
        
        // Exclude specific booking if provided
        if ($excludeBookingId) {
            $query['_id'] = ['$ne' => $this->toObjectId($excludeBookingId)];
        }
        
        // Check if any bookings exist in the time slot
        $count = $this->collection->countDocuments($query);
        
        return $count > 0;
    }
    
    /**
     * Update a booking
     *
     * @param string $id The booking ID
     * @param array $data The booking data to update
     * @return bool True if successful, false otherwise
     */
    public function update(string $id, array $data): bool {
        $objectId = $this->toObjectId($id);
        if (!$objectId) {
            return false;
        }
        
        // Convert dates to MongoDB format
        if (isset($data['start_time'])) {
            $data['start_time'] = $this->toMongoDate($data['start_time']);
        }
        
        if (isset($data['end_time'])) {
            $data['end_time'] = $this->toMongoDate($data['end_time']);
        }
        
        // Add updated timestamp
        $data = array_merge($data, $this->timestamps(true));
        
        // Update booking document
        $result = $this->collection->updateOne(
            ['_id' => $objectId],
            ['$set' => $data]
        );
        
        return $result->getModifiedCount() > 0;
    }
    
    /**
     * Cancel a booking
     *
     * @param string $bookingId Booking ID
     * @return bool Success
     */
    public function cancel($bookingId) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($bookingId)],
                [
                    '$set' => [
                        'status' => 'cancelled',
                        'updated_at' => new UTCDateTime(time() * 1000)
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            throw new \Exception("Error cancelling booking: " . $e->getMessage());
        }
    }
    
    /**
     * Get booking by ID
     *
     * @param string $id Booking ID
     * @return array|null Booking data or null if not found
     */
    public function getById(string $id) {
        try {
            $bookingId = new ObjectId($id);
            $booking = $this->collection->findOne(['_id' => $bookingId]);
            
            return $booking ? $this->formatBooking($booking) : null;
        } catch (\Exception $e) {
            error_log("Error getting booking: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get bookings for a provider
     *
     * @param string $providerId Provider ID
     * @param array $filter Additional filter criteria
     * @return array Array of bookings
     */
    public function getProviderBookings(string $providerId, array $filter = []) {
        try {
            $query = array_merge(['provider_id' => new ObjectId($providerId)], $filter);
            
            $options = [
                'sort' => ['start_time' => 1]
            ];
            
            $bookings = [];
            $cursor = $this->collection->find($query, $options);
            
            foreach ($cursor as $booking) {
                $bookings[] = $this->formatBooking($booking);
            }
            
            return $bookings;
        } catch (\Exception $e) {
            error_log("Error getting provider bookings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Format booking data for API response
     *
     * @param array $booking Raw booking data from MongoDB
     * @return array Formatted booking
     */
    protected function formatBooking($booking): array {
        $result = [
            'id' => (string) $booking['_id'],
            'provider_id' => (string) $booking['provider_id'],
            'slot_id' => (string) $booking['slot_id'],
            'customer' => $booking['customer'],
            'notes' => $booking['notes'] ?? '',
            'status' => $booking['status'] ?? 'confirmed',
            'start_time' => $booking['start_time'] ?? null,
            'end_time' => $booking['end_time'] ?? null,
            'created_at' => isset($booking['created_at']) ? $booking['created_at']->toDateTime()->format('Y-m-d H:i:s') : null,
            'updated_at' => isset($booking['updated_at']) ? $booking['updated_at']->toDateTime()->format('Y-m-d H:i:s') : null
        ];
        
        return $result;
    }
    
    /**
     * Update booking status
     *
     * @param string $id Booking ID
     * @param string $status New status
     * @return bool Success flag
     */
    public function updateStatus(string $id, string $status): bool {
        try {
            $bookingId = new ObjectId($id);
            
            $result = $this->collection->updateOne(
                ['_id' => $bookingId],
                [
                    '$set' => [
                        'status' => $status,
                        'updated_at' => new UTCDateTime(time() * 1000)
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            error_log("Error updating booking status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get bookings with filtering, pagination and sorting
     * 
     * @param array $filter Filter criteria
     * @param int $page Page number (default: 1)
     * @param int $limit Items per page (default: 20)
     * @return array List of bookings
     */
    public function getBookings($filter = [], $page = 1, $limit = 20) {
        try {
            // Ensure $limit is at least 1 to prevent division by zero
            $limit = max(1, (int)$limit);
            $page = max(1, (int)$page);
            
            // Starting query
            $query = [];
            
            // Apply filters
            if (!empty($filter)) {
                // Provider filter
                if (isset($filter['provider_id'])) {
                    $query['provider_id'] = $filter['provider_id'];
                }
                
                // Client filter
                if (isset($filter['client_id'])) {
                    $query['client_id'] = $filter['client_id'];
                }
                
                // Status filter
                if (isset($filter['status'])) {
                    $query['status'] = $filter['status'];
                }
                
                // Date range filter
                if (isset($filter['date_range'])) {
                    $dateQuery = [];
                    
                    if (isset($filter['date_range']['start'])) {
                        // Convert to MongoDB UTCDateTime if needed
                        $startDate = $filter['date_range']['start'];
                        if (is_string($startDate)) {
                            $startDateTime = new \DateTime($startDate);
                            $dateQuery['$gte'] = new \MongoDB\BSON\UTCDateTime($startDateTime->getTimestamp() * 1000);
                        } else {
                            $dateQuery['$gte'] = $startDate;
                        }
                    }
                    
                    if (isset($filter['date_range']['end'])) {
                        // Convert to MongoDB UTCDateTime if needed
                        $endDate = $filter['date_range']['end'];
                        if (is_string($endDate)) {
                            $endDateTime = new \DateTime($endDate);
                            $endDateTime->setTime(23, 59, 59); // End of day
                            $dateQuery['$lte'] = new \MongoDB\BSON\UTCDateTime($endDateTime->getTimestamp() * 1000);
                        } else {
                            $dateQuery['$lte'] = $endDate;
                        }
                    }
                    
                    if (!empty($dateQuery)) {
                        $query['start_time'] = $dateQuery;
                    }
                }
            }
            
            error_log("Query filter for bookings: " . json_encode($query));
            
            // Calculate skip value for pagination
            $skip = ($page - 1) * $limit;
            
            // Get total count for pagination info
            if (method_exists($this->collection, 'countDocuments')) {
                $totalCount = $this->collection->countDocuments($query);
            } else if (method_exists($this->collection, 'count')) {
                $totalCount = $this->collection->count($query);
            } else {
                // Manual counting if neither method is available
                $countCursor = $this->collection->find($query);
                $totalCount = iterator_count($countCursor);
            }
            
            // Ensure $totalCount is numeric
            $totalCount = (int)$totalCount;
            
            // Get paginated results using options
            $options = [
                'skip' => $skip,
                'limit' => $limit,
                'sort' => ['start_time' => -1]  // Sort by date descending
            ];
            
            $cursor = $this->collection->find($query, $options);
            
            // Convert cursor to array
            $bookings = [];
            foreach ($cursor as $document) {
                $bookings[] = $this->formatBooking($document);
            }
            
            // Calculate pages, ensuring no division by zero
            $pages = ($limit > 0) ? ceil($totalCount / $limit) : 0;
            
            return [
                'items' => $bookings,
                'pagination' => [
                    'total' => $totalCount,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => $pages
                ]
            ];
        } catch (\Exception $e) {
            error_log("Error in getBookings: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}