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
    public function create() {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getJsonData();
        
        // Validate required fields
        $requiredFields = ['slot_id', 'customer'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                Response::json(['error' => "Missing required field: {$field}"], 400);
                return;
            }
        }
        
        // Add provider ID from auth
        $data['provider_id'] = $this->userId;
        
        // Get the slot to verify it exists and is available
        $slot = $this->availabilityModel->getSlot($data['slot_id'], $this->userId);
        
        if (!$slot) {
            Response::json(['error' => 'Slot not found'], 404);
            return;
        }
        
        if (isset($slot['is_available']) && $slot['is_available'] === false) {
            Response::json(['error' => 'This slot is no longer available'], 409);
            return;
        }
        
        // Add times from slot
        $data['start_time'] = $slot['start_time'];
        $data['end_time'] = $slot['end_time'];
        
        // Create booking
        $booking = $this->bookingModel->create($data);
        
        if (!$booking) {
            Response::json(['error' => 'Failed to create booking'], 500);
            return;
        }
        
        // Generate meeting links if applicable
        $this->generateMeetingLinks($booking['id']);
        
        Response::json([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking' => $booking
        ], 201);
    }
    
    /**
     * Generate meeting links for a booking
     * 
     * @param string $bookingId Booking ID
     */
    private function generateMeetingLinks($bookingId) {
        try {
            $digitalSambaController = new \App\Controllers\DigitalSambaController();
            $digitalSambaController->generateMeetingLinks($bookingId);
        } catch (\Exception $e) {
            error_log("Failed to generate meeting links: " . $e->getMessage());
            // Continue without links - non-critical failure
        }
    }
    
    /**
     * Get list of bookings
     */
    public function index() {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get query parameters
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
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
        
        try {
            // Get bookings
            $result = $this->bookingModel->getBookings($filter, $page, $limit);
            Response::json($result);
        } catch (\Exception $e) {
            error_log("Error retrieving bookings: " . $e->getMessage());
            Response::json(['error' => 'Failed to retrieve bookings'], 500);
        }
    }
    
    /**
     * Get a specific booking by ID
     */
    public function view($id = null) {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get booking ID from URL if not provided
        $id = $id ?? $this->getIdFromPath();
        
        if (!$id) {
            Response::json(['error' => 'Booking ID is required'], 400);
            return;
        }
        
        // Get booking
        $booking = $this->bookingModel->getById($id);
        
        if (!$booking) {
            Response::json(['error' => 'Booking not found'], 404);
            return;
        }
        
        // Check permissions
        if ($booking['provider_id'] !== $this->userId && $this->userRole !== 'admin') {
            Response::json(['error' => 'You do not have permission to view this booking'], 403);
            return;
        }
        
        Response::json($booking);
    }
    
    /**
     * Cancel a booking
     */
    public function cancel($id = null) {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get booking ID from URL if not provided
        $id = $id ?? $this->getIdFromPath();
        
        if (!$id) {
            Response::json(['error' => 'Booking ID is required'], 400);
            return;
        }
        
        // Get booking
        $booking = $this->bookingModel->getById($id);
        
        if (!$booking) {
            Response::json(['error' => 'Booking not found'], 404);
            return;
        }
        
        // Check permissions
        if ($booking['provider_id'] !== $this->userId && $this->userRole !== 'admin') {
            Response::json(['error' => 'You do not have permission to cancel this booking'], 403);
            return;
        }
        
        // Check if already cancelled
        if ($booking['status'] === 'cancelled') {
            Response::json(['error' => 'Booking is already cancelled'], 400);
            return;
        }
        
        // Cancel booking
        $success = $this->bookingModel->update($id, ['status' => 'cancelled']);
        
        if ($success) {
            // Make the slot available again if needed
            if (!empty($booking['slot_id'])) {
                $this->availabilityModel->updateSlot($booking['slot_id'], 
                    ['is_available' => true], $booking['provider_id']);
            }
            
            Response::json([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);
        } else {
            Response::json(['error' => 'Failed to cancel booking'], 500);
        }
    }
    
    /**
     * Route booking detail requests
     */
    public function details() {
        $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
        
        // Check if path pattern is /booking/{id}/...
        if (count($pathParts) >= 2) {
            $id = $pathParts[1];
            
            // Check for specific action
            $action = $pathParts[2] ?? '';
            
            if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
                return $this->cancel($id);
            }
            
            // Default to view
            return $this->view($id);
        }
        
        Response::json(['error' => 'Invalid URL pattern'], 404);
    }
}