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
     * @return array|bool The booking document or false on failure
     */
    public function create(array $data) {
        error_log("BOOKING MODEL: Creating booking with data: " . json_encode($data));
        
        try {
            // Validate required data
            if (empty($data['provider_id'])) {
                error_log("BOOKING MODEL ERROR: Missing provider_id field");
                return false;
            }
            
            // Create the booking document
            $booking = [
                'provider_id' => $this->toObjectId($data['provider_id']),
                'status' => $data['status'] ?? 'confirmed'
            ];
            
            // Add customer_id if available, otherwise use customer object data
            if (!empty($data['customer_id'])) {
                $booking['customer_id'] = $this->toObjectId($data['customer_id']);
            } elseif (!empty($data['customer']) && is_array($data['customer'])) {
                // For public bookings, we don't have a customer_id, just customer info
                $booking['customer'] = $data['customer'];
            } else {
                error_log("BOOKING MODEL ERROR: Missing customer information - need either customer_id or customer object");
                return false;
            }
            
            // Add optional fields
            $optionalFields = ['service_id', 'notes', 'slot_id'];
            foreach ($optionalFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'slot_id') {
                        $booking[$field] = $this->toObjectId($data[$field]);
                    } else {
                        $booking[$field] = $data[$field];
                    }
                }
            }
            
            // Add start and end times
            if (!empty($data['start_time'])) {
                $booking['start_time'] = $this->toMongoDate($data['start_time']);
            }
            
            if (!empty($data['end_time'])) {
                $booking['end_time'] = $this->toMongoDate($data['end_time']);
            }
            
            // Add timestamps
            $booking = array_merge($booking, $this->timestamps());
            
            error_log("BOOKING MODEL: Inserting booking document into database: " . json_encode($booking));
            
            // Insert booking
            $result = $this->collection->insertOne($booking);
            
            if ($result->getInsertedCount() > 0) {
                $bookingId = (string)$result->getInsertedId();
                error_log("BOOKING MODEL: Booking inserted with ID: " . $bookingId);
                
                // If slot_id is provided, mark the slot as unavailable
                if (!empty($data['slot_id']) && !empty($data['provider_id'])) {
                    error_log("BOOKING MODEL: Marking slot as unavailable");
                    $this->availabilityModel->updateSlot(
                        $data['slot_id'],
                        ['is_available' => false],
                        $data['provider_id']
                    );
                }
                
                // Get the created booking
                $createdBooking = $this->getById($bookingId);
                if (!$createdBooking) {
                    $createdBooking = ['id' => $bookingId];
                }
                
                return $createdBooking;
            }
            
            error_log("BOOKING MODEL ERROR: Failed to insert booking");
            return false;
        } catch (\Exception $e) {
            error_log("BOOKING MODEL ERROR: Exception occurred while creating booking: " . $e->getMessage());
            error_log("BOOKING MODEL ERROR: " . $e->getTraceAsString());
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
     * @param string|ObjectId $id The booking ID
     * @param array $data The booking data to update
     * @return bool True if successful, false otherwise
     */
    public function update($id, array $data) {
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
        return $this->updateStatus($bookingId, 'cancelled');
    }
    
    /**
     * Get booking by ID
     *
     * @param string|ObjectId $id Booking ID
     * @return array|null Booking data
     */
    public function getById($id) {
        try {
            // Handle string or ObjectId
            $objectId = ($id instanceof \MongoDB\BSON\ObjectId) ? $id : new \MongoDB\BSON\ObjectId($id);
            
            $booking = $this->collection->findOne(['_id' => $objectId]);
            
            if (!$booking) {
                return null;
            }
            
            return $this->formatDocument($booking);
        } catch (\Exception $e) {
            error_log("Error getting booking by ID: " . $e->getMessage());
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
            'customer_id' => (string)($booking['customer_id'] ?? ''),
            'status' => $booking['status'] ?? 'confirmed',
        ];
        
        // Add customer object if it exists
        if (isset($booking['customer'])) {
            $result['customer'] = $booking['customer'];
        }
        
        // Add optional fields
        $optionalFields = ['service_id', 'notes', 'slot_id', 'provider_link'];
        foreach ($optionalFields as $field) {
            if (isset($booking[$field])) {
                if ($field === 'slot_id') {
                    $result[$field] = (string)$booking[$field];
                } else {
                    $result[$field] = $booking[$field];
                }
            }
        }
        
        // Process date fields
        $dateFields = ['start_time', 'end_time', 'created_at', 'updated_at'];
        foreach ($dateFields as $field) {
            if (isset($booking[$field])) {
                if ($booking[$field] instanceof UTCDateTime) {
                    // Convert MongoDB UTCDateTime to string
                    $result[$field] = $booking[$field]->toDateTime()->format('Y-m-d\TH:i:s');
                } else {
                    $result[$field] = $booking[$field];
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
        return $this->update($id, ['status' => $status]);
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
                // Provider filter
                if (isset($filter['provider_id'])) {
                    $query['provider_id'] = $this->toObjectId($filter['provider_id']);
                }
                
                // Customer filter
                if (isset($filter['customer_id'])) {
                    $query['customer_id'] = $this->toObjectId($filter['customer_id']);
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
                        $dateQuery['$gte'] = new UTCDateTime($startDateTime->getTimestamp() * 1000);
                    }
                    
                    if (isset($filter['date_range']['end'])) {
                        $endDate = $filter['date_range']['end'];
                        $endDateTime = new \DateTime($endDate);
                        $endDateTime->setTime(23, 59, 59);
                        $dateQuery['$lte'] = new UTCDateTime($endDateTime->getTimestamp() * 1000);
                    }
                    
                    if (!empty($dateQuery)) {
                        $query['start_time'] = $dateQuery;
                    }
                }
            }
            
            // Get total count
            $totalCount = $this->collection->countDocuments($query);
            
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
            
            foreach ($cursor as $document) {
                $bookings[] = $this->formatBooking($document);
            }
            
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
            error_log("Error in getBookings: " . $e->getMessage());
            return [
                'items' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }
    
    /**
     * Add meeting links to a booking
     * 
     * @param string $id Booking ID
     * @param string $providerLink Provider's meeting link
     * @param string $customerLink Customer's meeting link
     * @param string $meetingId Optional meeting ID
     * @return bool Success flag
     */
    public function addMeetingLinks($id, $providerLink, $customerLink, $meetingId = null) {
        try {
            // Get the current booking
            $booking = $this->getById($id);
            if (!$booking) {
                return false;
            }
            
            $updateData = [
                'provider_link' => $providerLink
            ];
            
            // Update customer object with link
            $customer = $booking['customer'] ?? [];
            if (!is_array($customer)) {
                $customer = ['id' => $booking['customer_id'] ?? $id];
            }
            $customer['customer_link'] = $customerLink;
            $updateData['customer'] = $customer;
            
            // Add meeting ID if provided
            if ($meetingId) {
                $updateData['meeting_id'] = $meetingId;
            }
            
            return $this->update($id, $updateData);
        } catch (\Exception $e) {
            error_log("Error adding meeting links: " . $e->getMessage());
            return false;
        }
    }
}

