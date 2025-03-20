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
     * @param array|object $booking Raw booking data from MongoDB
     * @return array Formatted booking
     */
    protected function formatBooking($booking): array {
        // Convert to array if object
        if (is_object($booking)) {
            $booking = (array)$booking;
        }
        
        $result = [
            'id' => (string)($booking['_id'] ?? ''),
            'provider_id' => (string)($booking['provider_id'] ?? ''),
            'notes' => $booking['notes'] ?? '',
            'status' => $booking['status'] ?? 'confirmed',
        ];
        
        // Process customer data
        if (isset($booking['customer'])) {
            $customerData = $booking['customer'];
            
            // Convert object to array if needed
            if (is_object($customerData)) {
                $customerData = (array)$customerData;
                error_log("Converted customer object to array");
            }
            
            if (is_array($customerData)) {
                // We have an array now, whether it was originally or after conversion
                $result['customer'] = $customerData;
                
                // Log customer data for debugging
                error_log("Customer data found in booking: " . json_encode($customerData));
                
                // Ensure the customer data has necessary fields
                if (empty($result['customer']['name'])) {
                    // Try to find a name in other possible fields
                    if (!empty($booking['customer_name'])) {
                        $result['customer']['name'] = $booking['customer_name'];
                        error_log("Using customer_name from booking: " . $booking['customer_name']);
                    } else {
                        error_log("Customer name is missing in booking data");
                        $result['customer']['name'] = 'N/A';
                    }
                }
                
                if (empty($result['customer']['email'])) {
                    // Try to find email in other possible fields
                    if (!empty($booking['customer_email'])) {
                        $result['customer']['email'] = $booking['customer_email'];
                        error_log("Using customer_email from booking: " . $booking['customer_email']);
                    } else {
                        error_log("Customer email is missing in booking data");
                        $result['customer']['email'] = 'N/A';
                    }
                }
                
                if (empty($result['customer']['phone'])) {
                    // Try to find phone in other possible fields
                    if (!empty($booking['customer_phone'])) {
                        $result['customer']['phone'] = $booking['customer_phone'];
                        error_log("Using customer_phone from booking: " . $booking['customer_phone']);
                    } else {
                        error_log("Customer phone is missing in booking data");
                        $result['customer']['phone'] = 'N/A';
                    }
                }
            } else {
                error_log("Customer field is not an array or object: " . gettype($booking['customer']));
                // If it's a string, try to use it as a name
                if (is_string($booking['customer'])) {
                    $result['customer'] = [
                        'name' => $booking['customer'],
                        'email' => 'N/A',
                        'phone' => 'N/A'
                    ];
                    error_log("Using customer string as name: " . $booking['customer']);
                } else {
                    $result['customer'] = [
                        'name' => 'N/A',
                        'email' => 'N/A',
                        'phone' => 'N/A'
                    ];
                }
            }
        } else {
            // Check for flat customer fields
            $name = $booking['customer_name'] ?? 'N/A';
            $email = $booking['customer_email'] ?? 'N/A';
            $phone = $booking['customer_phone'] ?? 'N/A';
            
            error_log("No customer object found, using flat fields: name={$name}, email={$email}");
            
            $result['customer'] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ];
        }
        
        // Add slot_id if exists
        if (isset($booking['slot_id'])) {
            $result['slot_id'] = (string)$booking['slot_id'];
        }
        
        // Process date fields
        $dateFields = ['start_time', 'end_time', 'date', 'created_at', 'updated_at'];
        foreach ($dateFields as $field) {
            if (isset($booking[$field])) {
                if ($booking[$field] instanceof \MongoDB\BSON\UTCDateTime) {
                    // Convert MongoDB UTCDateTime to string
                    $result[$field] = $booking[$field]->toDateTime()->format('Y-m-d\TH:i:s');
                } else {
                    $result[$field] = $booking[$field];
                }
            }
        }
        
        // Add any additional fields
        foreach ($booking as $key => $value) {
            if (!isset($result[$key]) && $key !== '_id') {
                if ($value instanceof \MongoDB\BSON\UTCDateTime) {
                    $result[$key] = $value->toDateTime()->format('Y-m-d\TH:i:s');
                } else if ($value instanceof \MongoDB\BSON\ObjectId) {
                    $result[$key] = (string)$value;
                } else {
                    $result[$key] = $value;
                }
            }
        }
        
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
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array List of bookings and pagination info
     */
    public function getBookings($filter = [], $page = 1, $limit = 20) {
        try {
            // Ensure $limit and $page are valid
            $limit = max(1, (int)$limit);
            $page = max(1, (int)$page);
            
            // Starting query
            $query = [];
            
            // Apply filters
            if (!empty($filter)) {
                // Provider filter - convert string ID to MongoDB ObjectId
                if (isset($filter['provider_id'])) {
                    $providerId = $filter['provider_id'];
                    
                    // Debug log
                    error_log("Original provider_id filter value: " . json_encode($providerId));
                    
                    // Handle provider_id as MongoDB ObjectId
                    if (is_string($providerId) && strlen($providerId) === 24) {
                        try {
                            $query['provider_id'] = new \MongoDB\BSON\ObjectId($providerId);
                            error_log("Converted provider_id to MongoDB ObjectId");
                        } catch (\Exception $e) {
                            error_log("Failed to convert provider_id to ObjectId: " . $e->getMessage());
                            // Fallback to string comparison
                            $query['provider_id'] = $providerId;
                        }
                    } else {
                        // Use as is
                        $query['provider_id'] = $providerId;
                    }
                }
                
                // Status filter
                if (isset($filter['status'])) {
                    $query['status'] = $filter['status'];
                }
                
                // Date range filter
                if (isset($filter['date_range'])) {
                    $dateQuery = [];
                    
                    if (isset($filter['date_range']['start'])) {
                        $startDate = $filter['date_range']['start'];
                        $startDateTime = new \DateTime($startDate);
                        $startDateTime->setTime(0, 0, 0);
                        $dateQuery['$gte'] = new \MongoDB\BSON\UTCDateTime($startDateTime->getTimestamp() * 1000);
                        error_log("Start date filter: " . $startDateTime->format('Y-m-d H:i:s'));
                    }
                    
                    if (isset($filter['date_range']['end'])) {
                        $endDate = $filter['date_range']['end'];
                        $endDateTime = new \DateTime($endDate);
                        $endDateTime->setTime(23, 59, 59);
                        $dateQuery['$lte'] = new \MongoDB\BSON\UTCDateTime($endDateTime->getTimestamp() * 1000);
                        error_log("End date filter: " . $endDateTime->format('Y-m-d H:i:s'));
                    }
                    
                    if (!empty($dateQuery)) {
                        $query['start_time'] = $dateQuery;
                    }
                }
            }
            
            // Add debug logging
            error_log("Final MongoDB query: " . json_encode($query));
            
            // Get total count
            $totalCount = $this->collection->countDocuments($query);
            error_log("Total matching documents: " . $totalCount);
            
            // Calculate skip value for pagination
            $skip = ($page - 1) * $limit;
            
            // Define options for find
            $options = [
                'skip' => $skip,
                'limit' => $limit,
                'sort' => ['start_time' => -1]
            ];
            
            // Get bookings
            $cursor = $this->collection->find($query, $options);
            $bookings = [];
            $count = 0;
            
            foreach ($cursor as $document) {
                $count++;
                $booking = $this->formatBooking($document);
                error_log("Processing booking ID: " . ($booking['id'] ?? 'unknown'));
                $bookings[] = $booking;
            }
            
            error_log("Retrieved {$count} booking documents");
            
            return [
                'items' => $bookings,
                'pagination' => [
                    'total' => $totalCount,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalCount / $limit)
                ]
            ];
        } catch (\Exception $e) {
            error_log("Error in getBookings: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}

