<?php
/**
 * Availability Controller
 * 
 * Handles availability endpoints
 */

namespace App\Controllers;

use App\Models\AvailabilityModel;
use App\Utils\Response;
use MongoDB\BSON\ObjectId;

class AvailabilityController extends BaseController {
    private $availabilityModel;
    
    public function __construct() {
        $this->availabilityModel = new AvailabilityModel();
    }
    
    /**
     * Default index action
     * Get available slots
     */
    public function index() {
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get date range from query parameters
        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days'));
        
        $slots = $this->availabilityModel->getSlots($userId, $startDate, $endDate);
        
        Response::json([
            'success' => true,
            'message' => 'Availability slots retrieved',
            'data' => $slots  // Changed from 'slots' to 'data' to match frontend expectation
        ]);
    }
    
    /**
     * Set availability slots - THIS IS THE MISSING METHOD
     */
    public function set() {
        // Require authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get JSON data
        $data = $this->getJsonData();
        
        // Validate slots
        if (!isset($data['slots']) || !is_array($data['slots']) || empty($data['slots'])) {
            Response::json(['error' => 'Invalid slots data'], 400);
            return;
        }
        
        // Set availability slots
        $result = $this->availabilityModel->addSlots($userId, $data['slots']);
        
        if ($result) {
            Response::json([
                'message' => 'Availability slots added successfully',
                'count' => count($data['slots'])
            ]);
        } else {
            Response::json([
                'error' => 'Failed to add availability slots'
            ], 500);
        }
    }
    
    /**
     * Delete a slot
     * 
     * NOTE: This endpoint is called using the format: DELETE /availability/deleteSlot?id={slotId}
     * The ID is retrieved from the query parameter, not from the URL path.
     */
    public function deleteSlot() {
        // Ensure the request method is DELETE
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::json(['error' => 'Invalid request method. Use DELETE.'], 405);
            return;
        }

        // Get ID from query parameter
        $id = $_GET['id'] ?? null;
        if (!$id) {
            Response::json(['error' => 'Missing slot ID'], 400);
            return;
        }

        // Check authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Pass BOTH parameters to the model method - this model method requires both ID and userId
        $result = $this->availabilityModel->deleteSlot($id, $userId);
        
        if ($result) {
            Response::json(['success' => true, 'message' => 'Slot deleted successfully']);
        } else {
            Response::json(['success' => false, 'error' => 'Failed to delete slot'], 400);
        }
    }
    
    /**
     * Get single slot
     * 
     * @param string $id Slot ID
     */
    public function getSlot($id) {
        // Check authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get slot
        $slot = $this->availabilityModel->findById($id);
        
        if ($slot) {
            Response::json([
                'message' => 'Slot retrieved successfully',
                'slot' => $slot
            ]);
        } else {
            Response::json(['error' => 'Slot not found'], 404);
        }
    }
    
    /**
     * Update a slot
     * 
     * @param string $id Slot ID
     */
    public function updateSlot($id) {
        // Check authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get JSON data
        $data = $this->getJsonData();
        
        // Update slot
        $result = $this->availabilityModel->updateSlot($id, $data);
        
        if ($result) {
            Response::json(['message' => 'Slot updated successfully']);
        } else {
            Response::json(['error' => 'Failed to update slot'], 400);
        }
    }
    
    /**
     * Authenticate the user and return user ID
     *
     * @return string|bool User ID if authenticated, false otherwise
     */
    private function authenticateUser() {
        // Check for JWT token in Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            // Token-based authentication
            $token = substr($authHeader, 7);
            $decoded = $this->jwtAuth->validateToken($token);
            
            if ($decoded && isset($decoded->data->id)) {
                return $decoded->data->id;
            }
        } else {
            // Session-based authentication
            $this->session->start();
            $userId = $this->session->get('user_id');
            
            if ($userId) {
                return $userId;
            }
        }
        
        // Not authenticated
        Response::json(['error' => 'Authentication required'], 401);
        return false;
    }
    
    /**
     * Validate date string format (YYYY-MM-DD)
     *
     * @param string $date Date string
     * @return bool True if valid
     */
    private function validateDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate date-time string format (YYYY-MM-DD HH:MM:SS)
     *
     * @param string $dateTime Date-time string
     * @return bool True if valid
     */
    private function validateDateTime($dateTime) {
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $d && $d->format('Y-m-d H:i:s') === $dateTime;
    }
    
    /**
     * Validate that time slots are in 30-minute intervals
     *
     * @param string $startTime Start time
     * @param string $endTime End time
     * @return bool True if valid
     */
    private function validateTimeInterval($startTime, $endTime) {
        // Convert to timestamps
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        
        // Calculate time difference in minutes
        $diffMinutes = ($end - $start) / 60;
        
        // Check if start time is on a 30-minute boundary
        $startMinutes = (int)date('i', $start);
        if ($startMinutes !== 0 && $startMinutes !== 30) {
            return false;
        }
        
        // Check if end time is on a 30-minute boundary
        $endMinutes = (int)date('i', $end);
        if ($endMinutes !== 0 && $endMinutes !== 30) {
            return false;
        }
        
        // Check if total time is a multiple of 30 minutes
        return $diffMinutes % 30 === 0 && $diffMinutes > 0;
    }

    /**
     * Generate availability slots
     */
    public function generate() {
        error_log("Starting generate method");
        
        try {
            // Check authentication
            $userId = $this->getUserId();
            if (!$userId) {
                error_log("Authentication failed");
                Response::json(['error' => 'Authentication required'], 401);
                return;
            }
            
            error_log("User authenticated with ID: {$userId}");
            
            // Get JSON data
            $data = $this->getJsonData();
            error_log("Received data: " . json_encode($data));
            
            // Validate required fields
            if (!isset($data['start_date']) || !isset($data['end_date']) || 
                !isset($data['daily_start_time']) || !isset($data['daily_end_time']) || 
                !isset($data['slot_duration']) || !isset($data['days_of_week'])) {
                error_log("Missing required fields");
                Response::json(['error' => 'Missing required fields'], 400);
                return;
            }
            
            // Validate date formats
            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);
            
            if ($startDate === false || $endDate === false) {
                error_log("Invalid date format");
                Response::json(['error' => 'Invalid date format'], 400);
                return;
            }
            
            // Validate time formats
            $startTime = strtotime($data['daily_start_time']);
            $endTime = strtotime($data['daily_end_time']);
            
            if ($startTime === false || $endTime === false) {
                error_log("Invalid time format");
                Response::json(['error' => 'Invalid time format'], 400);
                return;
            }
            
            // Convert daily start/end times to timestamps
            $dailyStart = strtotime($data['daily_start_time']);
            $dailyEnd = strtotime($data['daily_end_time']);
            
            if ($dailyEnd <= $dailyStart) {
                error_log("End time must be after start time");
                Response::json(['error' => 'End time must be after start time'], 400);
                return;
            }
            
            // Generate slots
            $slots = [];
            $slotDuration = intval($data['slot_duration']) * 60; // Convert to seconds
            
            error_log("Generating slots from {$data['start_date']} to {$data['end_date']}");
            error_log("Daily time range: {$data['daily_start_time']} to {$data['daily_end_time']}");
            error_log("Slot duration: {$data['slot_duration']} minutes");
            error_log("Days of week: " . json_encode($data['days_of_week']));
            
            // Convert daily times to hours and minutes
            $startHour = date('H', $dailyStart);
            $startMinute = date('i', $dailyStart);
            $endHour = date('H', $dailyEnd);
            $endMinute = date('i', $dailyEnd);
            
            for ($date = $startDate; $date <= $endDate; $date += 86400) { // 86400 = 24 hours in seconds
                $dayOfWeek = date('w', $date);
                
                if (in_array($dayOfWeek, $data['days_of_week'])) {
                    // Set the current time to the start of the day
                    $currentTime = mktime($startHour, $startMinute, 0, date('m', $date), date('d', $date), date('Y', $date));
                    $dayEndTime = mktime($endHour, $endMinute, 0, date('m', $date), date('d', $date), date('Y', $date));
                    
                    while ($currentTime < $dayEndTime) {
                        $slotEndTime = $currentTime + $slotDuration;
                        
                        if ($slotEndTime <= $dayEndTime) {
                            $slots[] = [
                                'start_time' => date('Y-m-d H:i:s', $currentTime),
                                'end_time' => date('Y-m-d H:i:s', $slotEndTime)
                            ];
                        }
                        
                        $currentTime = $slotEndTime;
                    }
                }
            }
            
            error_log("Generated " . count($slots) . " slots");
            
            // Add slots to database
            $result = $this->availabilityModel->addSlots($userId, $slots);
            
            if ($result) {
                error_log("Successfully added slots to database");
                
                // Get the newly created slots
                $createdSlots = $this->availabilityModel->getSlots(
                    $userId,
                    $data['start_date'],
                    $data['end_date']
                );
                
                Response::json([
                    'success' => true,
                    'message' => 'Availability slots generated successfully',
                    'count' => count($slots),
                    'data' => $createdSlots
                ]);
            } else {
                error_log("Failed to add slots to database");
                Response::json([
                    'success' => false,
                    'error' => 'Failed to generate availability slots. Some slots may already exist.'
                ], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in generate method: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            Response::json([
                'success' => false,
                'error' => 'An error occurred while generating availability slots'
            ], 500);
        }
    }

    /**
     * Delete all availability slots for the current user
     */
    public function deleteAll() {
        // Ensure the request method is DELETE
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::json(['error' => 'Invalid request method. Use DELETE.'], 405);
            return;
        }

        // Check authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Delete all slots for the user
        $result = $this->availabilityModel->deleteAllSlots($userId);
        
        if ($result) {
            Response::json([
                'success' => true,
                'message' => 'All availability slots deleted successfully'
            ]);
        } else {
            Response::json([
                'success' => false,
                'error' => 'Failed to delete all availability slots'
            ], 500);
        }
    }
}