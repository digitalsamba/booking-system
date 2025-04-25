<?php
/**
 * Booking Controller
 * 
 * Handles booking-related operations
 */

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\AvailabilityModel;
use App\Models\UserModel; // Use UserModel instead of ProviderModel // Added ProviderModel
use App\Utils\Response;
use App\Utils\JwtAuth; 
use App\Utils\Email\EmailNotificationService;

class BookingController extends BaseController {
    private $bookingModel;
    private $userModel; // Use UserModel instead of ProviderModel // Added providerModel
    private $emailService;
    protected $userId;
    protected $userRole;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->bookingModel = new BookingModel();
        $this->userModel = new UserModel(); // Initialize UserModel // Instantiate providerModel
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
                // Pass the required models to the constructor
                $digitalSambaController = new \App\Controllers\DigitalSambaController(
                    $this->bookingModel, 
                    $this->userModel
                ); 
                error_log("BookingController: Instantiated DigitalSambaController with models");
                $digitalSambaController->generateMeetingLinks($bookingId);
                error_log("BookingController: Returned from generateMeetingLinks for booking ID: " . $bookingId);
                
                // Refresh booking data to include links
                error_log("BookingController: Attempting to refresh booking data with ID: " . $bookingId);
                $booking = $this->bookingModel->getById($bookingId);
                error_log("BookingController: Successfully refreshed booking data for ID: " . $bookingId);

            } catch (\Exception $e) {
                error_log("BookingController: EXCEPTION during meeting link generation/refresh: " . $e->getMessage());
                error_log("BookingController: Stack Trace: " . $e->getTraceAsString());
                // Continue without links
            }
            
            // Send email notifications
            error_log("BookingController: Proceeding to send email notifications for booking ID: " . $bookingId);
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
     * @param string $id Booking ID from route parameter
     */
    public function view(string $id): void
    {
        // Authenticate user
        if (!$this->authenticate()) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // ID is now passed directly as an argument, no need for getIdParam()
        // $id = $this->getIdParam(); 
        if (empty($id)) {
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
     * @param string $id Booking ID from route parameter
     */
    public function cancel(string $id): void
    {
        // Authenticate user
        if (!$this->authenticate()) {
            Response::json(['error' => 'Authentication required'], 401);
            return;
        }
        
        // ID is now passed directly as an argument, no need for getIdParam()
        // $id = $this->getIdParam(); 
        if (empty($id)) { 
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
        $result = $this->bookingModel->cancel($id, $this->userId);
        
        if (!$result) {
            Response::json(['error' => 'Failed to cancel booking'], 500);
            return;
        }
        
        // Send cancellation email
        try {
             $this->emailService->sendBookingCancellation($bookingData); // Pass original data
        } catch (\Exception $e) {
            error_log("Failed to send cancellation email: " . $e->getMessage());
            // Do not fail the request if email fails
        }
        
        Response::json(['message' => 'Booking cancelled successfully']);
    }
    
    /**
     * Authenticate user and set $this->userId and $this->userRole
     *
     * @return bool True if authenticated, false otherwise
     */
    protected function authenticate(): bool {
        $userId = $this->getUserId(); // Use BaseController method
        if ($userId) {
            $this->userId = $userId;
            // TODO: Fetch user role properly if needed for fine-grained access control
            // For now, assume role check might happen within methods if required
            $user = $this->userModel->findById($userId); // Fetch user to potentially get role
            $this->userRole = $user['role'] ?? 'user'; // Get role, default to 'user'
            return true;
        }
        return false;
    }
    
    // Remove getIdParam as it's replaced by route parameters
    /*
    private function getIdParam()
    {
        // Get ID from PATH_INFO set by the old router
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        $parts = explode('/', trim($pathInfo, '/'));
        
        // Assuming format /booking/{id} or /booking/{id}/cancel
        if (isset($parts[1])) {
            return $parts[1];
        }
        return null;
    }
    */

    // getUserId() is provided by BaseController, no need to redefine
    /*
    protected function getUserId(): ?string {
        // Implementation moved to BaseController
    }
    */

    // getJsonData() is provided by BaseController, no need to redefine
    /*
    protected function getJsonData(): array {
        // Implementation moved to BaseController
    }
    */
}