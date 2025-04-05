<?php
/**
 * Booking Controller
 * 
 * Handles booking-related operations
 */

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\AvailabilityModel;
use App\Utils\Response;
use App\Utils\JwtAuth; // Change this from JwtUtil to JwtAuth
use App\Utils\Email\EmailNotificationService;

class BookingController extends BaseController {
    private $bookingModel;
    private $emailService;
    protected $userId;
    protected $userRole;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->bookingModel = new BookingModel();
        $this->emailService = new EmailNotificationService();
    }
    
    /**
     * Create a new booking
     * Public endpoint - does not require authentication
     */
    public function create() {
        // Get JSON request data
        $data = $this->getJsonData();
        
        // Validate required fields
        if (empty($data['slot_id']) || empty($data['provider_username']) || empty($data['customer'])) {
            Response::json(['error' => 'Required fields missing'], 400);
            return;
        }
        
        // Check customer data
        if (empty($data['customer']['name']) || empty($data['customer']['email'])) {
            Response::json(['error' => 'Customer name and email are required'], 400);
            return;
        }
        
        // Create the booking
        $result = $this->bookingModel->create($data);
        
        if (!$result) {
            Response::json(['error' => 'Failed to create booking'], 500);
            return;
        }
        
        // After creating the booking successfully:
        $bookingId = isset($result['_id']) ? (string)$result['_id'] : null;
        
        if ($bookingId) {
            // Get the created booking
            $booking = $this->bookingModel->getById($bookingId);
            
            // Generate meeting links
            try {
                $digitalSambaController = new \App\Controllers\DigitalSambaController();
                $digitalSambaController->generateMeetingLinks($bookingId);
                
                // Refresh booking data to include links
                $booking = $this->bookingModel->getById($bookingId);
            } catch (\Exception $e) {
                error_log("Failed to generate meeting links: " . $e->getMessage());
                // Continue without links
            }
            
            // Send email notifications
            try {
                // Send confirmation to customer
                $this->emailService->sendBookingConfirmation($bookingId);
                
                // Send notification to provider
                $this->emailService->sendBookingNotification($bookingId);
            } catch (\Exception $e) {
                error_log("Failed to send email notifications: " . $e->getMessage());
                // Continue without sending emails
            }
            
            Response::json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking' => $booking
            ], 201);
        } else {
            Response::json(['error' => 'Failed to create booking'], 500);
        }
    }
    
    /**
     * Get list of bookings
     */
    public function index() {
        // Authenticate user
        if (!$this->authenticate()) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        error_log("Authenticated user ID: " . $this->userId);
        
        // Get query parameters
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // Build filter
        $filter = ['provider_id' => $this->userId];
        
        if ($startDate || $endDate) {
            $filter['date_range'] = [];
            
            if ($startDate) {
                $filter['date_range']['start'] = $startDate;
            }
            
            if ($endDate) {
                $filter['date_range']['end'] = $endDate;
            }
        }
        
        if ($status) {
            $filter['status'] = $status;
        }
        
        error_log("Fetching bookings with filter: " . json_encode($filter));
        
        try {
            // Get bookings
            $result = $this->bookingModel->getBookings($filter, $page, $limit);
            
            // Add debugging info
            error_log("Retrieved " . count($result['items']) . " bookings");
            
            // Return response - pass the entire result with items and pagination
            Response::json($result);
        } catch (\Exception $e) {
            error_log("Error retrieving bookings: " . $e->getMessage());
            Response::json([
                'error' => 'Failed to retrieve bookings',
                'details' => DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get a specific booking by ID
     * Requires authentication
     */
    public function view() {
        // Authenticate user
        if (!$this->authenticate()) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get booking ID from URL
        $id = $this->getIdParam();
        if (!$id) {
            Response::json(['error' => 'Booking ID is required'], 400);
            return;
        }
        
        // Get booking
        $booking = $this->bookingModel->getById($id);
        
        // Check if booking exists
        if (!$booking) {
            Response::json(['error' => 'Booking not found'], 404);
            return;
        }
        
        // Check if booking belongs to this provider
        if ($booking['provider_id'] !== $this->userId && $this->userRole !== 'admin') {
            Response::json(['error' => 'You do not have permission to view this booking'], 403);
            return;
        }
        
        // Return booking
        Response::json($booking);
    }
    
    /**
     * Cancel a booking
     * Requires authentication
     */
    public function cancel() {
        // Authenticate user
        if (!$this->authenticate()) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // Get booking ID from URL
        $id = $this->getIdParam();
        if (!$id) {
            Response::json(['error' => 'Booking ID is required'], 400);
            return;
        }
        
        // Get booking
        $booking = $this->bookingModel->getById($id);
        
        // Check if booking exists
        if (!$booking) {
            Response::json(['error' => 'Booking not found'], 404);
            return;
        }
        
        // Check if booking belongs to this provider
        if ($booking['provider_id'] !== $this->userId && $this->userRole !== 'admin') {
            Response::json(['error' => 'You do not have permission to cancel this booking'], 403);
            return;
        }
        
        // Check if booking is already cancelled
        if ($booking['status'] === 'cancelled') {
            Response::json(['error' => 'Booking is already cancelled'], 400);
            return;
        }
        
        // Store booking data before cancelling
        $bookingData = $booking;
        
        // Cancel booking
        $success = $this->bookingModel->cancelBooking($id);
        
        if ($success) {
            // Send cancellation email
            try {
                $this->emailService->sendBookingCancellation($id, $bookingData);
            } catch (\Exception $e) {
                error_log("Failed to send cancellation email: " . $e->getMessage());
                // Continue without sending email
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
     * Handle details action for URL pattern: /booking/{id}
     */
    public function details() {
        // If request method is PUT, treat as an update/cancel request
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // Check if this is a cancel request
            $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
            $lastPart = end($pathParts);
            
            if ($lastPart === 'cancel') {
                $this->cancel();
                return;
            }
            
            // Other update methods could go here
            Response::json(['error' => 'Method not allowed'], 405);
            return;
        }
        
        // Default to view action
        $this->view();
    }
    
    /**
     * Authenticate the current user and set user properties
     * 
     * @return bool True if authenticated, false otherwise
     */
    protected function authenticate(): bool {
        // Use the getUserId method from BaseController
        $userId = parent::getUserId();
        
        if (!$userId) {
            return false;
        }
        
        // Get the token to extract additional info
        $token = $this->getBearerToken();
        
        if (!$token) {
            return false;
        }
        
        try {
            $secret = defined('JWT_SECRET') ? JWT_SECRET : 'default_secret_change_this';
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
            
            // Store user information from token
            $this->userId = $userId;
            $this->userRole = $decoded->data->role ?? 'user';
            
            return true;
        } catch (\Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ID parameter from URL
     * 
     * @return string|null ID or null if not found
     */
    private function getIdParam() {
        $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
        
        // For URLs like /booking/{id}
        if (count($pathParts) >= 2) {
            return $pathParts[1];
        }
        
        return null;
    }

    /**
     * Get user ID from authentication token
     * 
     * @return string|null User ID if authenticated, null otherwise
     */
    protected function getUserId(): ?string {
        // Get token from header
        $token = \App\Utils\JwtAuth::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        // Validate token
        $tokenData = \App\Utils\JwtAuth::validateToken($token);
        
        if (!$tokenData || empty($tokenData->data->user_id)) {
            return null;
        }
        
        return $tokenData->data->user_id;
    }
    
    /**
     * Get JSON data from request
     * 
     * @return array JSON data
     */
    protected function getJsonData(): array {
        return parent::getJsonData();
    }
}