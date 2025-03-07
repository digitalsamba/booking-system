<?php
/**
 * Availability Controller
 * 
 * Handles availability endpoints
 */

namespace App\Controllers;

use App\Models\AvailabilityModel;
use App\Utils\Response;
use App\Utils\JwtAuth;
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
    public function index(): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get date range from query parameters
        $startDate = $this->getQueryParam('start_date', date('Y-m-d'));
        $endDate = $this->getQueryParam('end_date', date('Y-m-d', strtotime('+7 days')));
        
        $slots = $this->availabilityModel->getSlots($this->userId, $startDate, $endDate);
        
        $this->success([
            'message' => 'Availability slots retrieved',
            'slots' => $slots
        ]);
    }
    
    /**
     * Add availability slots
     */
    public function add(): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get JSON data
        $data = $this->getJsonData();
        
        // Validate slots
        if (!isset($data['slots']) || !is_array($data['slots']) || empty($data['slots'])) {
            $this->error('Invalid slots data - "slots" array is required', 400);
            return;
        }
        
        // Add slots
        $result = $this->availabilityModel->addSlots($this->userId, $data['slots']);
        
        if ($result) {
            $this->success([
                'message' => 'Availability slots added successfully',
                'count' => count($data['slots'])
            ]);
        } else {
            $this->error('Failed to add availability slots', 500);
        }
    }

    /**
     * Delete a slot
     * 
     * @param string $id Slot ID
     */
    public function deleteSlot($id): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Delete slot
        $result = $this->availabilityModel->deleteSlot($id);
        
        if ($result) {
            $this->success(['message' => 'Slot deleted successfully']);
        } else {
            $this->error('Failed to delete slot', 400);
        }
    }
    
    /**
     * Get single slot
     * 
     * @param string $id Slot ID
     */
    public function getSlot($id): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get slot
        $slot = $this->availabilityModel->findById($id);
        
        if ($slot) {
            $this->success([
                'message' => 'Slot retrieved successfully',
                'slot' => $slot
            ]);
        } else {
            $this->error('Slot not found', 404);
        }
    }
    
    /**
     * Update a slot
     * 
     * @param string $id Slot ID
     */
    public function updateSlot($id): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get JSON data
        $data = $this->getJsonData();
        
        // Update slot
        $result = $this->availabilityModel->updateSlot($id, $data);
        
        if ($result) {
            $this->success(['message' => 'Slot updated successfully']);
        } else {
            $this->error('Failed to update slot', 400);
        }
    }    
   
    
    /**
     * Validate that time slots are in 30-minute intervals
     *
     * @param string $startTime Start time
     * @param string $endTime End time
     * @return bool True if valid
     */
    private function validateTimeInterval($startTime, $endTime): bool {
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
    public function generate(): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get JSON data
        $data = $this->getJsonData();
        
        // Validate required fields
        $requiredFields = ['start_date', 'end_date', 'slot_duration', 'daily_start_time', 'daily_end_time', 'days_of_week'];
        $missing = $this->validateRequiredFields($data, $requiredFields);
        if ($missing) {
            $this->error("Missing required fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Additional validations
        if (!$this->validateDateFormat($data['start_date']) || !$this->validateDateFormat($data['end_date'])) {
            $this->error('Invalid date format. Use YYYY-MM-DD', 400);
            return;
        }
        
        if (!$this->validateTimeFormat($data['daily_start_time']) || !$this->validateTimeFormat($data['daily_end_time'])) {
            $this->error('Invalid time format. Use HH:MM', 400);
            return;
        }
        
        // Generate and add slots
        $slots = $this->generateSlots(
            $this->userId,
            $data['start_date'],
            $data['end_date'],
            $data['daily_start_time'],
            $data['daily_end_time'],
            (int)$data['slot_duration'],
            $data['days_of_week']
        );
        
        if (empty($slots)) {
            $this->error('No slots could be generated with the given parameters', 400);
            return;
        }
        
        $result = $this->availabilityModel->addSlots($this->userId, $slots);
        
        if ($result) {
            $this->success([
                'message' => 'Availability slots generated successfully',
                'count' => count($slots)
            ]);
        } else {
            $this->error('Failed to save generated availability slots', 500);
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
    private function generateSlots($userId, $startDate, $endDate, $dailyStartTime, $dailyEndTime, $slotDuration, $daysOfWeek): array {
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
     * Get availability for the current provider or a specified provider
     * 
     * This endpoint handles both authenticated and public requests
     */
    public function getAvailability(): void {
        try {
            // Check if provider_id is specified in the query parameters
            $providerId = $this->getQueryParam('provider_id');
            $startDate = $this->getQueryParam('start_date', date('Y-m-d'));
            $endDate = $this->getQueryParam('end_date', date('Y-m-d', strtotime('+7 days')));
            
            // If provider_id is provided, treat as public query
            if ($providerId) {
                $slots = $this->availabilityModel->getPublicAvailability($providerId, $startDate, $endDate);
                
                $this->success([
                    'slots' => $slots
                ]);
                return;
            }
            
            // No provider_id provided, so we need authentication
            if (!$this->requireAuth()) {
                return;
            }
            
            // Get authenticated user's availability
            $slots = $this->availabilityModel->getSlots($this->userId, $startDate, $endDate, true);
            
            $this->success([
                'slots' => $slots
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch availability', 500, ['details' => $e->getMessage()]);
        }
    }

    /**
     * Format slots by date
     * 
     * @param array $slots Array of slots
     * @return array Slots grouped by date
     */
    private function formatSlotsByDate(array $slots): array {
        $formattedSlots = [];
        
        foreach ($slots as $slot) {
            // Extract date from start_time
            $date = substr($slot['start_time'], 0, 10);
            
            // Extract time from start_time and end_time
            $startTime = substr($slot['start_time'], 11, 5);
            $endTime = substr($slot['end_time'], 11, 5);
            
            if (!isset($formattedSlots[$date])) {
                $formattedSlots[$date] = [
                    'date' => $date,
                    'slots' => []
                ];
            }
            
            $formattedSlots[$date]['slots'][] = [
                'id' => $slot['id'],
                'start' => $startTime,
                'end' => $endTime,
                'available' => $slot['is_available'] ?? true
            ];
        }
        
        // Convert to indexed array
        return array_values($formattedSlots);
    }
}