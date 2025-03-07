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
        $this->debug("Constructor initialized with all models");
    }
    
    /**
     * Default index action - API information
     * 
     * This method merges functionality from DefaultController
     */
    public function index(): void {
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
    public function availability(): void {
        try {
            $this->debug("Fetching availability");
            
            // Get query parameters
            $username = $this->getQueryParam('username');
            $startDate = $this->getQueryParam('start_date', date('Y-m-d'));
            $endDate = $this->getQueryParam('end_date', date('Y-m-d', strtotime('+7 days')));
            
            $this->debug("Availability request parameters", [
                'username' => $username,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            if (!$username) {
                $this->debug("Missing username parameter");
                $this->error('Username is required', 400);
                return;
            }
            
            // Find user by username
            $user = $this->userModel->findByUsername($username);
            
            $this->debug("User lookup result", $user ? 'User found' : 'User not found');
            
            if (!$user) {
                $this->debug("User not found", ['username' => $username]);
                $this->error('User not found', 404);
                return;
            }
            
            // Use 'id' field
            $userId = $user['id'] ?? null;
            
            if (!$userId) {
                $this->debug("No ID found in user data", ['user_fields' => array_keys($user)]);
                $this->error('User ID not found', 500);
                return;
            }
            
            $this->debug("Getting availability for user", ['id' => $userId]);
            
            // Get availability for the user - use the already instantiated model
            $slots = $this->availabilityModel->getSlots($userId, $startDate, $endDate, false);
            $this->debug("Retrieved availability slots", ['count' => count($slots)]);
            
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
            $this->debug("Exception in public availability", $e->getMessage());
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
    public function booking(): void {
        // Get request data
        $data = $this->getJsonData();
        $this->debug("Received booking data", $data);
        
        // Validate required fields
        $requiredFields = ['slot_id', 'provider_username', 'customer'];
        $missing = $this->validateRequiredFields($data, $requiredFields);
        if ($missing) {
            $this->debug("Missing required fields", $missing);
            $this->error("Missing required fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Validate customer data
        $customerFields = ['name', 'email'];
        $missingCustomerFields = $this->validateRequiredFields($data['customer'], $customerFields);
        if ($missingCustomerFields) {
            $this->debug("Missing customer fields", $missingCustomerFields);
            $this->error("Missing customer fields: " . implode(', ', $missingCustomerFields), 400);
            return;
        }
        
        // Find provider by username
        $this->debug("Looking up provider with username", $data['provider_username']);
        $provider = $this->userModel->findByUsername($data['provider_username']);
        
        if (!$provider) {
            $this->debug("Provider not found", ['username' => $data['provider_username']]);
            $this->error('Provider not found', 404);
            return;
        }
        
        // Use 'id' field instead of '_id'
        $providerId = $provider['id'] ?? null;
        
        if (!$providerId) {
            $this->debug("Provider ID not found in provider data", ['provider_fields' => array_keys($provider)]);
            $this->error('Provider ID not found', 500);
            return;
        }
        
        $this->debug("Found provider with ID", $providerId);
        
        // Find the slot
        $this->debug("Looking up slot", [
            'slot_id' => $data['slot_id'], 
            'provider_id' => $providerId
        ]);
        $slot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
        
        if (!$slot) {
            $this->debug("Slot not found", ['slot_id' => $data['slot_id']]);
            $this->error('Slot not found', 404);
            return;
        }
        
        $this->debug("Found slot", [
            'id' => $slot['id'],
            'start_time' => $slot['start_time'],
            'is_available' => $slot['is_available'] ?? 'unknown'
        ]);
        
        // Check if slot is available
        if (isset($slot['is_available']) && $slot['is_available'] === false) {
            $this->debug("Slot is not available", ['slot_id' => $slot['id']]);
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
        
        $this->debug("Creating booking", $bookingData);
        
        $booking = $this->bookingModel->create($bookingData);
        
        if ($booking) {
            $this->debug("Booking created successfully", ['id' => $booking['id'] ?? 'unknown']);
            
            // Mark the slot as unavailable
            $slotMarked = $this->availabilityModel->markSlotUnavailable($data['slot_id'], $providerId);
            $this->debug("Slot marked unavailable", [
                'slot_id' => $data['slot_id'],
                'success' => $slotMarked ? 'yes' : 'no'
            ]);
            
            // Double-check that the slot was marked as unavailable
            $updatedSlot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
            $slotStatus = isset($updatedSlot['is_available']) ? 
                ($updatedSlot['is_available'] ? 'still available (ERROR)' : 'unavailable (OK)') : 'unknown';
                
            $this->debug("After booking, slot availability status", [
                'slot_id' => $data['slot_id'],
                'status' => $slotStatus
            ]);
            
            $this->success([
                'message' => 'Booking created successfully',
                'booking_id' => $booking['id'] ?? null,
                'provider' => $data['provider_username'],
                'customer' => $data['customer']['name'],
                'slot_status' => $slotStatus
            ], 201);
        } else {
            $this->debug("Failed to create booking");
            $this->error('Failed to create booking', 500);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function health(): void {
        $this->success([
            'status' => 'ok',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }
}