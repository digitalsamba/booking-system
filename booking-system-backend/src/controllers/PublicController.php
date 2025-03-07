<?php
/**
 * Public Controller
 * 
 * Handles public-facing API endpoints that don't require authentication
 */

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AvailabilityModel;
use App\Models\BookingModel;
use App\Utils\Response;

class PublicController extends BaseController {
    private $userModel;
    private $availabilityModel;
    private $bookingModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->availabilityModel = new AvailabilityModel();
        $this->bookingModel = new BookingModel();
        error_log("PUBLIC CONTROLLER: Constructor initialized with all models");
    }
    
    /**
     * Default index action - API information
     * 
     * This method merges functionality from DefaultController
     */
    public function index() {
        $this->success([
            'message' => 'Booking System API',
            'version' => '1.0.0',
            'public_endpoints' => [
                'GET /public - API information',
                'GET /public/availability - Get available slots for a provider',
                'POST /public/booking - Create a new booking'
            ]
        ]);
    }
    
    /**
     * Get public availability for a provider
     */
    public function availability() {
        try {
            error_log("PUBLIC CONTROLLER: Fetching availability");
            
            // Get query parameters
            $username = $this->getQueryParam('username');
            $startDate = $this->getQueryParam('start_date', date('Y-m-d'));
            $endDate = $this->getQueryParam('end_date', date('Y-m-d', strtotime('+7 days')));
            
            if (!$username) {
                $this->error('Username is required', 400);
                return;
            }
            
            // Find user by username
            $user = $this->userModel->findByUsername($username);
            
            error_log("User data from findByUsername: " . json_encode($user));
            
            if (!$user) {
                $this->error('User not found', 404);
                return;
            }
            
            // Use 'id' field
            $userId = $user['id'] ?? null;
            
            if (!$userId) {
                error_log("ERROR: No ID found in user data: " . json_encode($user));
                $this->error('User ID not found', 500);
                return;
            }
            
            error_log("Getting availability for user ID: " . $userId);
            
            // Get availability for the user - use the already instantiated model
            $slots = $this->availabilityModel->getSlots($userId, $startDate, $endDate, false);
            
            // Group slots by date
            $slotsByDate = $this->groupSlotsByDate($slots);
            
            // Return slots
            $this->success([
                'provider' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ],
                'slots' => $slots,
                'slots_by_date' => $slotsByDate
            ]);
        } catch (\Exception $e) {
            error_log("ERROR in public availability: " . $e->getMessage());
            $this->error('Failed to fetch availability', 500);
        }
    }
    
    /**
     * Group slots by date
     * 
     * @param array $slots Array of slots
     * @return array Slots grouped by date
     */
    private function groupSlotsByDate(array $slots): array {
        $groupedSlots = [];
        
        foreach ($slots as $slot) {
            // Extract date from start_time (format: YYYY-MM-DD HH:MM:SS)
            $date = substr($slot['start_time'], 0, 10);
            
            if (!isset($groupedSlots[$date])) {
                $groupedSlots[$date] = [
                    'date' => $date,
                    'slots' => []
                ];
            }
            
            // Extract time from start_time and end_time
            $startTime = substr($slot['start_time'], 11, 5);
            $endTime = substr($slot['end_time'], 11, 5);
            
            $groupedSlots[$date]['slots'][] = [
                'id' => $slot['id'],
                'start' => $startTime,
                'end' => $endTime
            ];
        }
        
        // Convert to indexed array
        return array_values($groupedSlots);
    }
    
    /**
     * Create a new booking
     */
    public function booking() {
        // Get request data
        $data = $this->getJsonData();
        error_log("BOOKING DEBUG: Received booking data: " . json_encode($data));
        
        // Validate required fields
        $requiredFields = ['slot_id', 'provider_username', 'customer'];
        $missing = $this->validateRequiredFields($data, $requiredFields);
        if ($missing) {
            error_log("BOOKING ERROR: Missing required fields: " . implode(', ', $missing));
            $this->error("Missing required fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Validate customer data
        $customerFields = ['name', 'email'];
        $missingCustomerFields = $this->validateRequiredFields($data['customer'], $customerFields);
        if ($missingCustomerFields) {
            error_log("BOOKING ERROR: Missing customer fields: " . implode(', ', $missingCustomerFields));
            $this->error("Missing customer fields: " . implode(', ', $missingCustomerFields), 400);
            return;
        }
        
        // Find provider by username
        error_log("BOOKING DEBUG: Looking up provider with username: " . $data['provider_username']);
        $provider = $this->userModel->findByUsername($data['provider_username']);
        
        if (!$provider) {
            error_log("BOOKING ERROR: Provider not found with username: " . $data['provider_username']);
            $this->error('Provider not found', 404);
            return;
        }
        
        // Use 'id' field instead of '_id'
        $providerId = $provider['id'] ?? null;
        
        if (!$providerId) {
            error_log("BOOKING ERROR: Provider ID not found in provider data: " . json_encode($provider));
            $this->error('Provider ID not found', 500);
            return;
        }
        
        error_log("BOOKING DEBUG: Found provider with ID: " . $providerId);
        
        // Find the slot
        error_log("BOOKING DEBUG: Looking up slot ID: " . $data['slot_id'] . " for provider ID: " . $providerId);
        $slot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
        
        if (!$slot) {
            error_log("BOOKING ERROR: Slot not found with ID: " . $data['slot_id']);
            $this->error('Slot not found', 404);
            return;
        }
        
        error_log("BOOKING DEBUG: Found slot: " . json_encode($slot));
        
        // Check if slot is available
        if (isset($slot['is_available']) && $slot['is_available'] === false) {
            error_log("BOOKING ERROR: Slot is not available. is_available=" . ($slot['is_available'] ? 'true' : 'false'));
            $this->error('This slot is no longer available', 409);
            return;
        }
        
        // Create the booking
        $bookingData = [
            'provider_id' => $providerId,
            'slot_id' => $data['slot_id'],
            'customer' => $data['customer'],
            'notes' => $data['notes'] ?? '',
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
            'status' => 'confirmed'
        ];
        
        error_log("BOOKING DEBUG: Creating booking with data: " . json_encode($bookingData));
        
        $booking = $this->bookingModel->create($bookingData);
        
        if ($booking) {
            error_log("BOOKING SUCCESS: Booking created with ID: " . ($booking['id'] ?? 'unknown'));
            
            // Mark the slot as unavailable
            $slotMarked = $this->availabilityModel->markSlotUnavailable($data['slot_id'], $providerId);
            error_log("BOOKING DEBUG: Slot marked unavailable: " . ($slotMarked ? 'yes' : 'no'));
            
            // Double-check that the slot was marked as unavailable
            $updatedSlot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
            error_log("BOOKING DEBUG: After booking, slot availability status: " . 
                (isset($updatedSlot['is_available']) ? ($updatedSlot['is_available'] ? 'true' : 'false') : 'unknown'));
            
            $this->success([
                'message' => 'Booking created successfully',
                'booking_id' => $booking['id'] ?? null,
                'provider' => $data['provider_username'],
                'customer' => $data['customer']['name'],
                'slot_status' => isset($updatedSlot['is_available']) ? ($updatedSlot['is_available'] ? 'still available (ERROR)' : 'unavailable (OK)') : 'unknown'
            ], 201);
        } else {
            error_log("BOOKING ERROR: Failed to create booking");
            $this->error('Failed to create booking', 500);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function health() {
        $this->success([
            'status' => 'ok',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }
}