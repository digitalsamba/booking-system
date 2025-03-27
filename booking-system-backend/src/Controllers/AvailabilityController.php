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
            'slots' => $slots
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
     * @param string $id Slot ID
     */
    public function deleteSlot($id) {
        // Check authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Delete slot
        $result = $this->availabilityModel->deleteSlot($id);
        
        if ($result) {
            Response::json(['message' => 'Slot deleted successfully']);
        } else {
            Response::json(['error' => 'Failed to delete slot'], 400);
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
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate date-time string format (YYYY-MM-DD HH:MM:SS)
     *
     * @param string $dateTime Date-time string
     * @return bool True if valid
     */
    private function validateDateTime($dateTime) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
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
     * Automatically creates slots based on date range, time range, and days of week
     */
    public function generate() {
        // Require authentication
        $userId = $this->getUserId();
        if (!$userId) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get JSON data
        $data = $this->getJsonData();
        
        // Validate required fields
        $requiredFields = ['start_date', 'end_date', 'slot_duration', 'daily_start_time', 'daily_end_time', 'days_of_week'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                Response::json(['error' => "Missing required field: $field"], 400);
                return;
            }
        }
        
        // Validate dates
        if (!$this->validateDateFormat($data['start_date']) || !$this->validateDateFormat($data['end_date'])) {
            Response::json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
            return;
        }
        
        // Validate time format
        if (!$this->validateTimeFormat($data['daily_start_time']) || !$this->validateTimeFormat($data['daily_end_time'])) {
            Response::json(['error' => 'Invalid time format. Use HH:MM'], 400);
            return;
        }
        
        // Validate slot duration (must be a positive integer)
        $slotDuration = (int)$data['slot_duration'];
        if ($slotDuration <= 0) {
            Response::json(['error' => 'Slot duration must be a positive integer'], 400);
            return;
        }
        
        // Validate days of week (must be an array of integers 0-6)
        if (!is_array($data['days_of_week']) || empty($data['days_of_week'])) {
            Response::json(['error' => 'Days of week must be a non-empty array'], 400);
            return;
        }
        
        foreach ($data['days_of_week'] as $day) {
            if (!is_numeric($day) || $day < 0 || $day > 6) {
                Response::json(['error' => 'Each day must be a number between 0 (Sunday) and 6 (Saturday)'], 400);
                return;
            }
        }
        
        // Generate slots
        $slots = $this->generateSlots(
            $userId,
            $data['start_date'],
            $data['end_date'],
            $data['daily_start_time'],
            $data['daily_end_time'],
            $slotDuration,
            $data['days_of_week']
        );
        
        // Add slots to database
        $result = $this->availabilityModel->addSlots($userId, $slots);
        
        if ($result) {
            Response::json([
                'message' => 'Availability slots generated successfully',
                'count' => count($slots),
                'slots' => $slots
            ]);
        } else {
            Response::json([
                'error' => 'Failed to generate availability slots'
            ], 500);
        }
    }

    /**
     * Generate time slots based on parameters
     * 
     * @param string $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $dailyStartTime Daily start time (HH:MM)
     * @param string $dailyEndTime Daily end time (HH:MM)
     * @param int $slotDuration Slot duration in minutes
     * @param array $daysOfWeek Days of week (0 = Sunday, 6 = Saturday)
     * @return array Array of slot objects
     */
    private function generateSlots($userId, $startDate, $endDate, $dailyStartTime, $dailyEndTime, $slotDuration, $daysOfWeek) {
        $slots = [];
        
        // Convert to DateTime objects
        $currentDate = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);
        $endDateTime->setTime(23, 59, 59); // End of the day
        
        // Loop through each day in the date range
        while ($currentDate <= $endDateTime) {
            $dayOfWeek = (int)$currentDate->format('w'); // 0 (Sunday) to 6 (Saturday)
            
            // Check if current day is in the selected days of week
            if (in_array($dayOfWeek, $daysOfWeek)) {
                // Parse daily start and end times
                $startParts = explode(':', $dailyStartTime);
                $endParts = explode(':', $dailyEndTime);
                
                // Set time to start time
                $timeSlotStart = clone $currentDate;
                $timeSlotStart->setTime((int)$startParts[0], (int)$startParts[1], 0);
                
                // Set time to end time
                $dailyEndDateTime = clone $currentDate;
                $dailyEndDateTime->setTime((int)$endParts[0], (int)$endParts[1], 0);
                
                // Generate time slots for the day
                while ($timeSlotStart < $dailyEndDateTime) {
                    // Calculate end time for this slot
                    $timeSlotEnd = clone $timeSlotStart;
                    $timeSlotEnd->add(new \DateInterval("PT{$slotDuration}M"));
                    
                    // Make sure end time doesn't exceed daily end time
                    if ($timeSlotEnd > $dailyEndDateTime) {
                        break;
                    }
                    
                    // Create slot object
                    $slot = [
                        'provider_id' => $userId,
                        'start_time' => $timeSlotStart->format('Y-m-d H:i:s'),
                        'end_time' => $timeSlotEnd->format('Y-m-d H:i:s'),
                        'is_available' => true,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Add to slots array
                    $slots[] = $slot;
                    
                    // Move to next slot
                    $timeSlotStart = $timeSlotEnd;
                }
            }
            
            // Move to next day
            $currentDate->add(new \DateInterval('P1D'));
        }
        
        return $slots;
    }

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date Date string
     * @return bool True if valid
     */
    private function validateDateFormat($date) {
        if (!is_string($date)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate time format (HH:MM)
     * 
     * @param string $time Time string
     * @return bool True if valid
     */
    private function validateTimeFormat($time) {
        if (!is_string($time)) {
            return false;
        }
        $t = \DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }
}