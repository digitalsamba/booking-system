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
// We don't need a separate ProviderModel import - UserModel already imported // Import ProviderModel
use App\Utils\Response;
use App\Utils\Email\EmailServiceFactory;
use MongoDB\BSON\ObjectId;
use App\Services\BrandingService;
use App\Utils\MongoDBHelper;

class PublicController extends BaseController {
    private $userModel;
    private $availabilityModel;
    private $bookingModel;
    private $brandingService;
    // We don't need a separate providerModel property - userModel is sufficient
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->availabilityModel = new AvailabilityModel();
        $this->bookingModel = new BookingModel(); // Initialize BookingModel properly
        $this->brandingService = new BrandingService(); // Add BrandingService initialization
        // No need to initialize ProviderModel - userModel is already initialized
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
        
        // Validate required fields and format check
        if (empty($data['slot_id'])) {
            error_log("BOOKING ERROR: Missing slot_id in request: " . json_encode($data));
            Response::json(['error' => 'Missing slot_id'], 400);
            return;
        }
        
        if (empty($data['provider_username'])) {
            error_log("BOOKING ERROR: Missing provider_username in request: " . json_encode($data));
            Response::json(['error' => 'Missing provider_username'], 400);
            return;
        }
        
        if (empty($data['customer'])) {
            error_log("BOOKING ERROR: Missing customer data in request: " . json_encode($data));
            Response::json(['error' => 'Missing customer data'], 400);
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
        
        // Log slot data for debugging
        error_log("BOOKING DEBUG: Slot data: " . json_encode($slot));
        
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
            error_log("BOOKING SUCCESS: Booking created with ID: " . (isset($booking['_id']) ? (string)$booking['_id'] : 'unknown'));
            
            // Double-check that the slot was marked as unavailable
            $updatedSlot = $this->availabilityModel->getSlot($data['slot_id'], $providerId);
            error_log("BOOKING DEBUG: After booking, slot availability status: " . 
                (isset($updatedSlot['is_available']) ? ($updatedSlot['is_available'] ? 'true' : 'false') : 'unknown'));
            
            // ======= RE-ENABLING DIGITAL SAMBA INTEGRATION =======
            // Generate meeting links if the provider has Digital Samba credentials
            $meetingLinks = null;
            $bookingId = isset($booking['_id']) ? (string)$booking['_id'] : null;
            $customerLink = ''; // Initialize customerLink
            
            if ($bookingId && isset($provider['team_id']) && !empty($provider['team_id']) && 
                isset($provider['developer_key']) && !empty($provider['developer_key'])) {
                
                // Ensure models are available - PublicController should have them initialized
                if (!isset($this->bookingModel) || !isset($this->userModel)) {
                    error_log("FATAL ERROR PREVENTED: BookingModel or UserModel not initialized in PublicController");
                    throw new \Exception("Required models not available in PublicController");
                }

                // Instantiate controller BEFORE the try block
                $digitalSambaController = new \App\Controllers\DigitalSambaController(
                    $this->bookingModel, 
                    $this->userModel
                ); 
                error_log("BOOKING DEBUG: Instantiated DigitalSambaController outside try block");

                try {
                    error_log("BOOKING DEBUG: Attempting to call generateMeetingLinks for booking ID: $bookingId");
                    // Call method inside the try block
                    $meetingLinksResponse = $digitalSambaController->generateMeetingLinks($bookingId); 
                    error_log("BOOKING DEBUG: Returned from Digital Samba link generation. Response structure: " . json_encode(array_keys($meetingLinksResponse ?? [])));
                    
                    // Check if link generation was successful and extract the link
                    $providerLink = null; // Initialize provider link
                    if (isset($meetingLinksResponse['success']) && $meetingLinksResponse['success'] === true) {
                        if (isset($meetingLinksResponse['links']['customer_link'])) {
                            $customerLink = $meetingLinksResponse['links']['customer_link'];
                            error_log("BOOKING DEBUG: Extracted customer link from successful DS response: " . substr($customerLink, 0, 30) . "...");
                        }
                        if (isset($meetingLinksResponse['links']['provider_link'])) {
                            $providerLink = $meetingLinksResponse['links']['provider_link']; // Extract provider link
                            error_log("BOOKING DEBUG: Extracted provider link from successful DS response: " . substr($providerLink, 0, 30) . "...");
                        }
                    } else {
                        error_log("BOOKING WARNING: Digital Samba link generation did not return a success status or links. Error: " . ($meetingLinksResponse['error'] ?? 'Unknown error'));
                    }
                } catch (\Throwable $e) { // Catch Throwable for potentially fatal errors
                    error_log("BOOKING ERROR: Throwable caught during Digital Samba link generation: " . $e->getMessage());
                    error_log("BOOKING ERROR: Trace: " . $e->getTraceAsString());
                    error_log("BOOKING INFO: Continuing with booking process despite meeting link failure");
                }
            } else {
                 // Add logging if the condition fails
                 error_log("BOOKING DEBUG: Skipping Digital Samba link generation (missing bookingId or provider credentials).");
                 // Keep minimal reason logging for troubleshooting
                 // if (!$bookingId) error_log("Reason: bookingId is missing or null."); 
                 // if (!isset($provider['team_id']) || empty($provider['team_id'])) error_log("Reason: Provider team_id is missing.");
                 // if (!isset($provider['developer_key']) || empty($provider['developer_key'])) error_log("Reason: Provider developer_key is missing.");
            }
            // ======= END RE-ENABLING DIGITAL SAMBA INTEGRATION =======

            // Send confirmation email to customer
            $emailSentSuccessfully = false;
            error_log("BOOKING EMAIL: ======= INITIATING EMAIL SENDING PROCESS =======");
            // error_log("BOOKING EMAIL: Booking created successfully, now sending confirmation email"); // Redundant
            
            try {
                // Verify all data needed for email is available
                if (empty($data['customer']['email'])) {
                    error_log("BOOKING EMAIL ERROR: Customer email is missing, cannot send confirmation");
                } else {
                    // error_log("BOOKING EMAIL: Recipient email: " . $data['customer']['email']); // Too verbose
                    // error_log("BOOKING EMAIL: Recipient name: " . $data['customer']['name']); // Too verbose
                    // error_log("BOOKING EMAIL: Provider name: " . ($provider['display_name'] ?? $provider['username'])); // Too verbose
                    // error_log("BOOKING EMAIL: Booking date: " . date('l, F j, Y', strtotime($slot['start_time']))); // Too verbose
                    
                    // Create email data array
                    $emailData = [
                        'booking_date' => date('l, F j, Y', strtotime($slot['start_time'])),
                        'start_time' => date('g:i A', strtotime($slot['start_time'])),
                        'end_time' => date('g:i A', strtotime($slot['end_time'])),
                        'booking_id' => isset($booking['_id']) ? (string)$booking['_id'] : 'N/A',
                        'notes' => $data['notes'] ?? '',
                        'customer_link' => $customerLink,
                        'company_name' => 'SambaConnect'
                    ];
                    
                    // error_log("BOOKING EMAIL: *** PRE-CALL *** About to call sendBookingConfirmationEmail method"); // Debug log
                    
                    // Call the email sending method
                    $emailResult = $this->sendBookingConfirmationEmail(
                        $data['customer']['email'],
                        $data['customer']['name'],
                        $provider['display_name'] ?? $provider['username'],
                        $emailData
                    );
                    // error_log("BOOKING EMAIL: *** POST-CALL *** Returned from sendBookingConfirmationEmail method"); // Debug log
                    
                    // error_log("BOOKING EMAIL: Email sending result: " . ($emailResult ? "SUCCESS" : "FAILED")); // Redundant
                    // error_log("BOOKING EMAIL: ======= EMAIL SENDING PROCESS COMPLETE ======="); // Redundant
                    $emailSentSuccessfully = $emailResult;
                }
            } catch (\Exception $e) {
                error_log("BOOKING EMAIL ERROR: Exception during email sending: " . $e->getMessage());
                error_log("BOOKING EMAIL ERROR: Exception trace: " . $e->getTraceAsString());
                // We don't want to fail the booking just because the email failed, so we continue
            }
            
            // Send notification email to provider
            try {
                // Make sure we have the necessary data objects
                if ($provider && $booking && $slot) {
                    error_log("BOOKING_PROVIDER_EMAIL: Attempting to send notification to provider: " . ($provider['email'] ?? 'N/A'));
                    
                    // Convert BSONDocuments to arrays before passing
                    $providerArray = (array) $provider;
                    $bookingArray = (array) $booking;
                    $slotArray = (array) $slot;
                    
                    $this->sendBookingNotificationEmailToProvider($providerArray, $bookingArray, $slotArray, $providerLink);
                } else {
                    error_log("BOOKING_PROVIDER_EMAIL ERROR: Missing provider, booking, or slot data. Cannot send notification.");
                }
            } catch (\Exception $e) {
                error_log("BOOKING_PROVIDER_EMAIL ERROR: Exception during provider notification sending: " . $e->getMessage());
                // Continue even if provider notification fails
            }
            
            // error_log("BOOKING DEBUG: *** CRITICAL POINT 1: About to send response"); // Debug log
            
            // Create response with the new booking
            Response::json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking_id' => isset($booking['_id']) ? (string)$booking['_id'] : null,
                'provider' => $data['provider_username'],
                'customer' => $data['customer']['name'],
                'slot_status' => isset($updatedSlot['is_available']) ? ($updatedSlot['is_available'] ? 'still available (ERROR)' : 'unavailable (OK)') : 'unknown'
            ], 201);
            
            // This will never execute if Response::json ends execution
            error_log("BOOKING DEBUG: *** CRITICAL POINT 2: After response");
            
            // Note: This code likely won't be reached if Response::json ends execution
        } else {
            error_log("BOOKING ERROR: Failed to create booking");
            Response::json(['error' => 'Failed to create booking'], 500);
        }
    }

    /**
     * Send a booking confirmation email to the customer
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $providerName Provider name
     * @param array $bookingData Booking details
     * @return bool Success status
     */
    private function sendBookingConfirmationEmail($email, $name, $providerName, $bookingData) {
        error_log("BOOKING EMAIL: ====== START BOOKING EMAIL PROCESS ======");
        error_log("BOOKING EMAIL: Sending confirmation email to $name <$email>");
        
        try {
            // Create email service
            // error_log("BOOKING EMAIL: Creating email service..."); // Too verbose
            $emailService = EmailServiceFactory::create();
            
            // Check if the service was created properly
            if (!$emailService) {
                error_log("BOOKING EMAIL ERROR: Email service could not be created!");
                return false;
            }
            
            $emailProviderName = get_class($emailService);
            error_log("BOOKING EMAIL: Email service created using provider: $emailProviderName");
            // error_log("BOOKING EMAIL: Email provider configured: " . ($emailService->isConfigured() ? 'YES' : 'NO')); // Keep error case only
            if (!$emailService->isConfigured()) {
                error_log("BOOKING EMAIL ERROR: Email provider ($emailProviderName) is NOT configured!");
                // Depending on requirements, you might want to return false here
            }
            
            // Prepare template data
            $templateData = [
                'customer_name' => $name,
                'provider_name' => $providerName, // This was incorrectly using the provider class name, should use the actual provider name passed in
                'booking_date' => $bookingData['booking_date'],
                'start_time' => $bookingData['start_time'],
                'end_time' => $bookingData['end_time'],
                'booking_id' => $bookingData['booking_id'],
                'customer_link' => $bookingData['customer_link'] ?? '',
                'notes' => $bookingData['notes'] ?? '',
                'company_name' => $bookingData['company_name'] ?? 'SambaConnect'
            ];
            
            // Get template path
            $templateFile = __DIR__ . '/../../templates/emails/booking_confirmation_html.php';
            // error_log("BOOKING EMAIL: Looking for template at: $templateFile"); // Too verbose
            
            // Check if template exists
            if (!file_exists($templateFile)) {
                error_log("BOOKING EMAIL ERROR: Template not found at $templateFile");
                return false;
            }
            
            // error_log("BOOKING EMAIL: Template found, rendering email content"); // Too verbose
            
            // Extract data for template
            extract($templateData);
            
            // Render template
            ob_start();
            include $templateFile;
            $htmlBody = ob_get_clean();
            
            // error_log("BOOKING EMAIL: HTML template rendered: " . (empty($htmlBody) ? 'EMPTY' : strlen($htmlBody) . ' bytes')); // Too verbose
            
            // Create plain text version
            $textBody = "Booking Confirmation\n\n" .
                        "Dear {$customer_name},\n\n" .
                        "Your booking with {$provider_name} has been confirmed.\n\n" .
                        "Booking Details:\n" .
                        "Date: {$booking_date}\n" .
                        "Time: {$start_time} - {$end_time}\n" .
                        "Booking ID: {$booking_id}\n\n";
            
            if (!empty($customer_link)) {
                $textBody .= "Meeting Link: {$customer_link}\n\n";
            }
            
            if (!empty($notes)) {
                $textBody .= "Additional Notes:\n{$notes}\n\n";
            }
            
            $textBody .= "Thank you for using our booking system.\n\n" .
                        "Regards,\n" .
                        $company_name;
            
            // error_log("BOOKING EMAIL: Text content prepared: " . strlen($textBody) . " bytes"); // Too verbose
            error_log("BOOKING EMAIL: Attempting to send email via provider $emailProviderName to: $email");
            
            // Send email
            // error_log("BOOKING EMAIL: Calling email service sendEmail method..."); // Too verbose
            $success = $emailService->sendEmail(
                $email,
                'Your Booking Confirmation',
                $textBody,
                $htmlBody
            );
            
            if ($success) {
                error_log("BOOKING EMAIL: SUCCESS! Confirmation email sent successfully to $email");
                error_log("BOOKING EMAIL: ====== END BOOKING EMAIL PROCESS ======");
                return true;
            } else {
                error_log("BOOKING EMAIL ERROR: FAILED to send confirmation email to $email via $emailProviderName");
                error_log("BOOKING EMAIL ERROR: Check provider logs for details");
                error_log("BOOKING EMAIL: ====== END BOOKING EMAIL PROCESS WITH ERRORS ======");
                return false;
            }
        } catch (\Exception $e) {
            error_log("BOOKING EMAIL ERROR: Exception thrown: " . $e->getMessage());
            error_log("BOOKING EMAIL ERROR: Exception trace: " . $e->getTraceAsString());
            error_log("BOOKING EMAIL ERROR: Exception in file: " . $e->getFile() . " on line " . $e->getLine());
            error_log("BOOKING EMAIL: ====== END BOOKING EMAIL PROCESS WITH EXCEPTION ======");
            return false;
        }
    }

    /**
     * Send a booking notification email to the provider
     * 
     * @param array $provider Provider details
     * @param array $booking Booking details
     * @param array $slot Slot details
     * @param string|null $providerLink Provider's meeting link
     * @return bool Success status
     */
    private function sendBookingNotificationEmailToProvider(array $provider, array $booking, array $slot, ?string $providerLink): bool {
        $providerEmail = $provider['email'] ?? null;
        $providerName = $provider['display_name'] ?? $provider['username'] ?? 'Provider';
        $customerName = $booking['customer']['name'] ?? 'Unknown Customer';
        $customerEmail = $booking['customer']['email'] ?? 'No email provided';

        if (!$providerEmail) {
            error_log("BOOKING_PROVIDER_EMAIL ERROR: Provider email is missing for ID: " . ($provider['_id'] ?? 'N/A'));
            return false;
        }

        error_log("BOOKING_PROVIDER_EMAIL: ====== START PROVIDER NOTIFICATION PROCESS ======");
        error_log("BOOKING_PROVIDER_EMAIL: Sending notification to $providerName <$providerEmail> for booking with $customerName");

        try {
            $emailService = EmailServiceFactory::create();
            if (!$emailService) {
                error_log("BOOKING_PROVIDER_EMAIL ERROR: Email service could not be created!");
                return false;
            }
            
            $emailProviderName = get_class($emailService);
            error_log("BOOKING_PROVIDER_EMAIL: Email service created using provider: $emailProviderName");
            if (!$emailService->isConfigured()) {
                error_log("BOOKING_PROVIDER_EMAIL ERROR: Email provider ($emailProviderName) is NOT configured!");
                // Potentially return false or use a fallback if critical
            }

            // Prepare template data
            $templateData = [
                'provider_name' => $providerName,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'booking_date' => date('l, F j, Y', strtotime($slot['start_time'])),
                'start_time' => date('g:i A', strtotime($slot['start_time'])),
                'end_time' => date('g:i A', strtotime($slot['end_time'])),
                'booking_id' => (string)($booking['_id'] ?? 'N/A'),
                'provider_link' => $providerLink ?? '', // Pass the provider's link
                'notes' => $booking['notes'] ?? '',
                'company_name' => 'SambaConnect' // Or use a config value
            ];

            // Get template path
            $templateFile = __DIR__ . '/../../templates/emails/booking_notification_provider_html.php';

            if (!file_exists($templateFile)) {
                error_log("BOOKING_PROVIDER_EMAIL ERROR: Template not found at $templateFile");
                return false;
            }

            // Render template
            extract($templateData);
            ob_start();
            include $templateFile;
            $htmlBody = ob_get_clean();

            // Create plain text version
            $textBody = "New Booking Notification\n\n" .
                        "Hello {$provider_name},\n\n" .
                        "You have a new booking from {$customer_name} ({$customer_email}).\n\n" .
                        "Booking Details:\n" .
                        "Date: {$booking_date}\n" .
                        "Time: {$start_time} - {$end_time}\n" .
                        "Booking ID: {$booking_id}\n\n";
            
            if (!empty($provider_link)) {
                $textBody .= "Your Meeting Link: {$provider_link}\n\n";
            }
            
            if (!empty($notes)) {
                $textBody .= "Customer Notes:\n{$notes}\n\n";
            }
            
            $textBody .= "You can view this booking in your dashboard.\n\n" .
                        "Regards,\n" .
                        $company_name;

            error_log("BOOKING_PROVIDER_EMAIL: Attempting to send email via provider $emailProviderName to: $providerEmail");

            // Send email
            $success = $emailService->sendEmail(
                $providerEmail,
                "New Booking Notification - {$customer_name} on {$templateData['booking_date']}",
                $textBody,
                $htmlBody
            );

            if ($success) {
                error_log("BOOKING_PROVIDER_EMAIL: SUCCESS! Notification email sent successfully to $providerEmail");
                error_log("BOOKING_PROVIDER_EMAIL: ====== END PROVIDER NOTIFICATION PROCESS ======");
                return true;
            } else {
                error_log("BOOKING_PROVIDER_EMAIL ERROR: FAILED to send notification email to $providerEmail via $emailProviderName");
                error_log("BOOKING_PROVIDER_EMAIL ERROR: Check provider logs for details");
                error_log("BOOKING_PROVIDER_EMAIL: ====== END PROVIDER NOTIFICATION PROCESS WITH ERRORS ======");
                return false;
            }
        } catch (\Exception $e) {
            error_log("BOOKING_PROVIDER_EMAIL ERROR: Exception thrown: " . $e->getMessage());
            error_log("BOOKING_PROVIDER_EMAIL ERROR: Exception trace: " . $e->getTraceAsString());
            error_log("BOOKING_PROVIDER_EMAIL: ====== END PROVIDER NOTIFICATION PROCESS WITH EXCEPTION ======");
            return false;
        }
    }

    /**
     * Get provider details by username
     * @param string $username Provider username from route parameter
     */
    public function getProviderDetails(string $username)
    {
        error_log("PUBLIC CONTROLLER: getProviderDetails request for username: {$username}");
        
        if (!$username) {
            error_log("PUBLIC CONTROLLER: No username provided for getProviderDetails");
            Response::json(['error' => 'Username is required'], 400);
            return;
        }
        
        // Find provider by username
        $provider = $this->userModel->findByUsername($username);
        
        if (!$provider) {
            error_log("PUBLIC CONTROLLER: Provider not found with username: {$username}");
            Response::json(['error' => 'Provider not found'], 404);
            return;
        }
        
        // Prepare response data (only public fields)
        $responseData = [
            'username' => $provider['username'],
            'display_name' => $provider['display_name'] ?? $provider['username'],
            'userId' => (string)$provider['_id'],
            // Add other public fields as needed, e.g., profile picture, description
        ];
        
        Response::json($responseData);
    }

    /**
     * Get public branding settings for a specific provider by User ID.
     * @param string $userId Provider's User ID from route parameter.
     */
    public function getBrandingSettings(string $userId)
    {
        error_log("[PublicController::getBrandingSettings] Request for userId: {$userId}");

        // Validate ObjectId format using try-catch
        try {
            new ObjectId($userId);
            // If constructor doesn't throw, format is valid
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            error_log("[PublicController::getBrandingSettings] Invalid userId format: {$userId}");
            Response::json(['error' => 'Invalid provider ID format'], 400);
            return;
        } catch (\Exception $e) { // Catch other potential errors during validation
             error_log("[PublicController::getBrandingSettings] Unexpected error validating userId {$userId}: " . $e->getMessage());
             Response::json(['error' => 'Error validating provider ID'], 500);
             return;
        }

        // Use the existing BrandingService
        // Ensure BrandingService is instantiated (should be in constructor)
        if (!isset($this->brandingService)) {
            // This ideally shouldn't happen if constructor is correct
            error_log("[PublicController::getBrandingSettings] BrandingService not initialized!");
            $this->brandingService = new \App\Services\BrandingService(); 
        }

        try {
            $rawSettings = $this->brandingService->getBrandingSettings($userId);

            if (!$rawSettings) {
                // It's okay if settings don't exist, return empty or default indicator?
                // For now, return 404 like the authenticated endpoint
                error_log("[PublicController::getBrandingSettings] No settings found for userId: {$userId}");
                Response::json(['message' => 'Branding settings not found for this provider'], 404);
                return;
            }

            // Format the settings using the helper - reuse logic from BrandingController
            $settings = MongoDBHelper::formatForApi($rawSettings);
            // Ensure userId is string (though we fetched by it)
            if (isset($settings['userId']) && $settings['userId'] instanceof \MongoDB\BSON\ObjectId) {
                 $settings['userId'] = (string) $settings['userId'];
            }

            // Only return publicly relevant fields (exclude internal stuff if any)
            // For now, return all fetched fields as the authenticated endpoint does
            error_log("[PublicController::getBrandingSettings] Returning settings for userId: {$userId}");
            Response::json($settings);

        } catch (\Exception $e) {
            error_log('[PublicController::getBrandingSettings] Error fetching settings for userId ' . $userId . ': ' . $e->getMessage());
            Response::json(['error' => 'Internal Server Error retrieving branding settings'], 500);
        }
    }
}