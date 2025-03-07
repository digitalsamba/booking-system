<?php
/**
 * Booking Controller
 * 
 * Handles booking-related operations
 */

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\AvailabilityModel;
use App\Models\UserModel;
use App\Utils\Response;

class BookingController extends BaseController {
    private $bookingModel;
    private $availabilityModel;
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->bookingModel = new BookingModel();
        $this->availabilityModel = new AvailabilityModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Create a new booking (authenticated endpoint)
     */
    public function create(): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getJsonData();
        
        // Validate required fields
        $requiredFields = ['slot_id', 'customer'];
        $missing = $this->validateRequiredFields($data, $requiredFields);
        if ($missing) {
            $this->error("Missing required fields: " . implode(', ', $missing), 400);
            return;
        }
        
        // Add provider ID from auth
        $data['provider_id'] = $this->userId;
        
        // Get the slot to verify it exists and is available
        $slot = $this->availabilityModel->getSlot($data['slot_id'], $this->userId);
        
        if (!$slot) {
            $this->error('Slot not found', 404);
            return;
        }
        
        if (isset($slot['is_available']) && $slot['is_available'] === false) {
            $this->error('This slot is no longer available', 409);
            return;
        }
        
        // Add times from slot
        $data['start_time'] = $slot['start_time'];
        $data['end_time'] = $slot['end_time'];
        
        // Create booking
        $booking = $this->bookingModel->create($data);
        
        if (!$booking) {
            $this->error('Failed to create booking', 500);
            return;
        }
        
        // Generate meeting links if applicable
        $this->generateMeetingLinks($booking['id']);
        
        $this->success([
            'message' => 'Booking created successfully',
            'booking' => $booking
        ], 201);
    }
    
    /**
     * Generate meeting links for a booking
     * 
     * @param string $bookingId Booking ID
     */
    private function generateMeetingLinks($bookingId): void {
        try {
            $this->debug("Attempting to generate meeting links", ['booking_id' => $bookingId]);
            $digitalSambaController = new \App\Controllers\DigitalSambaController();
            $digitalSambaController->generateMeetingLinks($bookingId);
        } catch (\Exception $e) {
            // Replace this error_log with debug()
            $this->debug("Failed to generate meeting links", $e->getMessage());
            // Continue without links - non-critical failure
        }
    }
    
    /**
     * Get list of bookings
     */
    public function index(): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get query parameters
        $startDate = $this->getQueryParam('start_date');
        $endDate = $this->getQueryParam('end_date');
        $status = $this->getQueryParam('status');
        $limit = (int)$this->getQueryParam('limit', 20);
        $page = (int)$this->getQueryParam('page', 1);
        
        // Build filter
        $filter = ['provider_id' => $this->userId];
        
        if ($startDate || $endDate) {
            $filter['date_range'] = [
                'start' => $startDate ?? null,
                'end' => $endDate ?? null
            ];
        }
        
        if ($status) {
            $filter['status'] = $status;
        }
        
        $this->debug("Getting bookings with filter", $filter);
        
        try {
            // Get bookings
            $result = $this->bookingModel->getBookings($filter, $page, $limit);
            $this->debug("Retrieved bookings", ['count' => count($result['bookings'] ?? [])]);
            $this->success($result);
        } catch (\Exception $e) {
            // Replace this error_log with debug()
            $this->debug("Error retrieving bookings", $e->getMessage());
            $this->error('Failed to retrieve bookings', 500);
        }
    }
    
    /**
     * Get a specific booking by ID
     */
    public function view(?string $id = null): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get booking ID from URL if not provided
        $id = $id ?? $this->getIdFromPath();
        
        if (!$id) {
            $this->error('Booking ID is required', 400);
            return;
        }
        
        // Get booking
        $booking = $this->bookingModel->getById($id);
        
        if (!$booking) {
            $this->error('Booking not found', 404);
            return;
        }
        
        // Check permissions
        if ($booking['provider_id'] !== $this->userId && $this->userRole !== 'admin') {
            $this->error('You do not have permission to view this booking', 403);
            return;
        }
        
        $this->success($booking);
    }
    
    /**
     * Cancel a booking
     * 
     * @param string|null $id Booking ID
     * @return void
     */
    public function cancel(?string $id = null): void {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get booking ID from URL if not provided
        $id = $id ?? $this->getIdFromPath();
        
        if (!$id) {
            $this->debug("Cancel booking attempt without ID");
            $this->error('Booking ID is required', 400);
            return;
        }
        
        $this->debug("Attempting to cancel booking", ['id' => $id]);
        
        // Get booking
        $booking = $this->bookingModel->getById($id);
        
        if (!$booking) {
            $this->debug("Booking not found for cancellation", ['id' => $id]);
            $this->error('Booking not found', 404);
            return;
        }
        
        // Check permissions
        if ($booking['provider_id'] !== $this->userId && $this->userRole !== 'admin') {
            $this->debug("Permission denied for booking cancellation", [
                'booking_provider' => $booking['provider_id'],
                'user_id' => $this->userId,
                'user_role' => $this->userRole ?? 'none'
            ]);
            $this->error('You do not have permission to cancel this booking', 403);
            return;
        }
        
        // Check if already cancelled
        if ($booking['status'] === 'cancelled') {
            $this->debug("Booking already cancelled", ['id' => $id]);
            $this->error('Booking is already cancelled', 400);
            return;
        }
        
        // Cancel booking
        $success = $this->bookingModel->update($id, ['status' => 'cancelled']);
        
        if ($success) {
            // Make the slot available again if needed
            if (!empty($booking['slot_id'])) {
                $this->debug("Restoring availability for slot", ['slot_id' => $booking['slot_id']]);
                $this->availabilityModel->updateSlot($booking['slot_id'], 
                    ['is_available' => true], $booking['provider_id']);
            }
            
            $this->debug("Booking cancelled successfully", ['id' => $id]);
            $this->success([
                'message' => 'Booking cancelled successfully'
            ]);
        } else {
            $this->debug("Failed to update booking status", ['id' => $id]);
            $this->error('Failed to cancel booking', 500);
        }
    }
    
    /**
     * Route booking detail requests
     */
    public function details(): void {
        $pathParts = $this->getPathParts();
        $this->debug("Processing booking details request", ['path_parts' => $pathParts]);
        
        // Check if path pattern is /booking/{id}/...
        if (count($pathParts) >= 2) {
            $id = $pathParts[1];
            
            // Check for specific action
            $action = $pathParts[2] ?? '';
            
            if ($action === 'cancel' && $this->isPut()) {
                $this->debug("Processing cancel action", ['id' => $id, 'method' => 'PUT']);
                $this->cancel($id);
                return;
            }
            
            // Default to view
            $this->debug("Processing view action", ['id' => $id]);
            $this->view($id);
            return;
        }
        
        $this->debug("Invalid booking URL pattern");
        $this->error('Invalid URL pattern', 404);
    }
}