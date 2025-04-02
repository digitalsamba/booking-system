<?php
/**
 * Public Controller
 * 
 * Handles public-facing API endpoints that don't require authentication
 */

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AvailabilityModel;
use App\Models\BookingModel; // Correct import for BookingModel
use App\Utils\Response;
use MongoDB\BSON\ObjectId;

class PublicController extends BaseController {
    private $userModel;
    private $availabilityModel;
    private $bookingModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->availabilityModel = new AvailabilityModel();
        $this->bookingModel = new BookingModel(); // Initialize BookingModel properly
        error_log("PUBLIC CONTROLLER: Constructor initialized with all models");
    }
    
    /**
     * Default index action
     */
    public function index() {
        Response::json([
            'message' => 'Public API endpoints',
            'endpoints' => [
                'GET /public/availability - Get available slots for a provider',
                'POST /public/booking - Create a new booking'
            ]
        ]);
    }
    
    /**
     * Get availability slots for a provider by username
     */
    public function availability() {
        // Get username from query parameters
        $username = isset($_GET['username']) ? $_GET['username'] : null;
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+7 days'));
        
        error_log("PUBLIC CONTROLLER: Availability request for username: {$username}, start_date: {$startDate}, end_date: {$endDate}");
        
        if (!$username) {
            error_log("PUBLIC CONTROLLER: No username provided");
            Response::json(['error' => 'Provider username is required'], 400);
            return;
        }
        
        // Find user by username
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            error_log("PUBLIC CONTROLLER: Provider not found with username: {$username}");
            Response::json(['error' => 'Provider not found'], 404);
            return;
        }
        
        error_log("PUBLIC CONTROLLER: Found provider with ID: " . (string)$user['_id']);
        
        // Get available slots
        $slots = $this->availabilityModel->getSlots($user['_id'], $startDate, $endDate);
        error_log("PUBLIC CONTROLLER: Retrieved " . count($slots) . " slots");
        
        if (empty($slots)) {
            error_log("PUBLIC CONTROLLER: No slots found for the specified date range");
        } else {
            error_log("PUBLIC CONTROLLER: First slot data: " . json_encode($slots[0]));
        }
        
        Response::json([
            'provider' => [
                'username' => $username,
                'id' => (string)$user['_id']
            ],
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'slots' => $slots
        ]);
    }
    
    /**
     * Create a new booking
     */
    public function booking() {
        // Get request data
        $data = $this->getJsonData();
        error_log("BOOKING DEBUG: Received booking data: " . json_encode($data));
        
        // Validate required fields
        if (empty($data['slot_id']) || empty($data['provider_username']) || empty($data['customer'])) {
            error_log("BOOKING ERROR: Missing required fields in booking request");
            Response::json(['error' => 'Missing required fields'], 400);
            return;
        }
        
        // Validate customer data
        if (empty($data['customer']['name']) || empty($data['customer']['email'])) {
            error_log("BOOKING ERROR: Missing customer name or email");
            Response::json(['error' => 'Customer name and email are required'], 400);
            return;
        }
        
        // Find provider by username
        error_log("BOOKING DEBUG: Looking up provider with username: " . $data['provider_username']);
        $provider = $this->userModel->findByUsername($data['provider_username']);
        
        if (!$provider) {
            error_log("BOOKING ERROR: Provider not found with username: " . $data['provider_username']);
            Response::json(['error' => 'Provider not found'], 404);
            return;
        }
        
        // Convert ObjectId to string for getSlot method
        $providerId = (string)$provider['_id'];
        error_log("BOOKING DEBUG: Found provider with ID: " . $providerId);
        
        // Find the slot
        error_log("BOOKING DEBUG: Looking up slot ID: " . $data['slot_id'] . " for provider ID: " . $providerId);
        $slot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
        
        if (!$slot) {
            error_log("BOOKING ERROR: Slot not found with ID: " . $data['slot_id']);
            Response::json(['error' => 'Slot not found'], 404);
            return;
        }
        
        error_log("BOOKING DEBUG: Found slot: " . json_encode($slot));
        
        // Check if slot is available
        if (empty($slot['is_available'])) {
            error_log("BOOKING ERROR: Slot is not available. is_available=" . ($slot['is_available'] ? 'true' : 'false'));
            Response::json(['error' => 'This slot is no longer available'], 409);
            return;
        }
        
        // Create the booking
        $bookingData = [
            'provider_id' => $providerId,
            'slot_id' => $data['slot_id'],
            'customer' => $data['customer'],
            'notes' => $data['notes'] ?? ''
        ];
        
        error_log("BOOKING DEBUG: Creating booking with data: " . json_encode($bookingData));
        
        // Create a new BookingModel instance if it doesn't exist
        if (!isset($this->bookingModel)) {
            error_log("BOOKING DEBUG: Initializing BookingModel");
            $this->bookingModel = new BookingModel();
        }
        
        $booking = $this->bookingModel->create($bookingData);
        
        if ($booking) {
            error_log("BOOKING SUCCESS: Booking created with ID: " . ($booking['id'] ?? 'unknown'));
            
            // Double-check that the slot was marked as unavailable
            $updatedSlot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
            error_log("BOOKING DEBUG: After booking, slot availability status: " . 
                (isset($updatedSlot['is_available']) ? ($updatedSlot['is_available'] ? 'true' : 'false') : 'unknown'));
            
            Response::json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking_id' => $booking['id'] ?? null,
                'provider' => $data['provider_username'],
                'customer' => $data['customer']['name'],
                'slot_status' => isset($updatedSlot['is_available']) ? ($updatedSlot['is_available'] ? 'still available (ERROR)' : 'unavailable (OK)') : 'unknown'
            ], 201);
        } else {
            error_log("BOOKING ERROR: Failed to create booking");
            Response::json(['error' => 'Failed to create booking'], 500);
        }
    }

    /**
     * Get provider details by username
     */
    public function getProviderDetails($username) {
        try {
            error_log("PUBLIC CONTROLLER: Fetching provider details for username: " . $username);
            
            // Find the user by username
            $user = $this->userModel->findByUsername($username);
            
            if (!$user) {
                error_log("PUBLIC CONTROLLER: Provider not found with username: " . $username);
                Response::json(['error' => 'Provider not found'], 404);
                return;
            }

            error_log("PUBLIC CONTROLLER: Found provider: " . json_encode($user));
            
            // Return only the necessary provider information
            Response::json([
                'id' => (string)$user['_id'],
                'display_name' => $user['display_name'] ?? $user['username'],
                'email' => $user['email'],
                'username' => $user['username']
            ]);
        } catch (\Exception $e) {
            error_log("PUBLIC CONTROLLER: Error in getProviderDetails: " . $e->getMessage());
            error_log("PUBLIC CONTROLLER: Stack trace: " . $e->getTraceAsString());
            Response::json(['error' => 'Error fetching provider details: ' . $e->getMessage()], 500);
        }
    }
}