<?php
/**
 * DigitalSambaController
 * 
 * Handles Digital Samba meeting creation and token generation
 * Uses the Digital Samba REST API to create rooms and generate participant tokens
 */

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\UserModel;
use App\Utils\Response;

class DigitalSambaController {
    private $apiBaseUrl;
    private $defaultSettings;
    private BookingModel $bookingModel;
    private UserModel $userModel;

    /**
     * Constructor - loads Digital Samba API configuration and models
     */
    public function __construct(BookingModel $bookingModel, UserModel $userModel) {
        $this->bookingModel = $bookingModel;
        $this->userModel = $userModel; // Store UserModel instance

        // Load Digital Samba configuration
        $configPath = defined('CONFIG_PATH') ? CONFIG_PATH : dirname(dirname(__DIR__)) . '/config';
        
        if (file_exists($configPath . '/digitalsamba.php')) {
            $config = require $configPath . '/digitalsamba.php';
            $this->apiBaseUrl = $config['api_base_url'] ?? 'https://api.digitalsamba.com/api/v1';
            $this->defaultSettings = $config['default_settings'] ?? [];
        } else {
            // Default values if config file doesn't exist
            $this->apiBaseUrl = 'https://api.digitalsamba.com/api/v1';
            $this->defaultSettings = [
                'privacy' => 'public',
                'features' => [
                    'chat' => true,
                    'screen_share' => true
                ]
            ];
            error_log("Warning: Digital Samba config file not found. Using default settings.");
        }
    }
    
    /**
     * Make an API request to Digital Samba
     * 
     * @param string $endpoint The API endpoint (without base URL)
     * @param string $method HTTP method (GET, POST, etc.)
     * @param array $data Request data for POST/PUT/PATCH
     * @param string $developerKey The provider's Digital Samba developer key
     * @return array Response data
     * @throws \Exception If the API request fails
     */
    private function apiRequest($endpoint, $method = 'GET', $data = null, $developerKey = null) {
        if (!$developerKey) {
            throw new \Exception("Developer key is required for Digital Samba API requests");
        }
        
        $url = $this->apiBaseUrl . '/' . ltrim($endpoint, '/');
        
        // Initialize cURL
        $curl = curl_init();
        
        // Set options
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $developerKey
        ];
        
        // Set options
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_VERBOSE => true,
            // Bypass SSL verification for development
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        // Add request body for POST/PUT/PATCH
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
            
            // Log the request data for debugging
            error_log("API Request to {$url}:\nMethod: {$method}\nHeaders: " . json_encode($headers) . "\nBody: {$jsonData}");
        } else {
            // Log the request data for debugging
            error_log("API Request to {$url}:\nMethod: {$method}\nHeaders: " . json_encode($headers));
        }
        
        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Log API call for debugging
        error_log("Digital Samba API {$method} {$endpoint} - Status: {$httpCode}");
        
        // Handle errors
        if ($error) {
            throw new \Exception("Digital Samba API request failed: " . $error);
        }
        
        // Log the response for debugging
        error_log("API Response ({$httpCode}):\n{$response}");
        
        // Parse response
        $responseData = json_decode($response, true);
        
        // Handle error responses
        if ($httpCode >= 400) {
            $errorMessage = isset($responseData['error']) ? $responseData['error'] : "API error (HTTP {$httpCode})";
            throw new \Exception("Digital Samba API error: " . $errorMessage);
        }
        
        return $responseData;
    }
    
    /**
     * Create a Digital Samba room for a booking
     * 
     * @param string $providerId The provider's user ID
     * @param string $bookingId The booking ID
     * @param array $bookingData Additional booking data
     * @return array Room data
     * @throws \Exception If room creation fails
     */
    private function createRoom($providerId, $bookingId, $bookingData = []) {
        // Get provider details to access their Digital Samba credentials
        $provider = $this->userModel->findById($providerId);
        
        if (!$provider) {
            throw new \Exception("Provider not found");
        }
        
        // Check for Digital Samba credentials
        $developerKey = $provider['developer_key'] ?? null;
        $teamId = $provider['team_id'] ?? null;
        
        if (!$developerKey || !$teamId) {
            throw new \Exception("Provider does not have complete Digital Samba credentials (developer_key and team_id are required)");
        }
        
        // Build friendly URL using booking ID and provider username
        $friendlyUrl = $provider['username'] . '-' . substr($bookingId, 0, 8);
        
        // Prepare room data
        $roomData = array_merge([
            'team_id' => $teamId, // Add the team_id - this is required by the Digital Samba API
            'friendly_url' => $friendlyUrl,
            'privacy' => $this->defaultSettings['privacy'] ?? 'public',
            'language' => 'en', // Use simple 'en' instead of 'en_US' which may not be supported
            
            // Add available roles for this room
            'roles' => [
                'moderator',
                'attendee'  // Using 'attendee' instead of 'participant'
            ],
            
            // Set default role for people joining the room
            'default_role' => 'attendee'  // Using 'attendee' instead of 'participant'
        ], $this->defaultSettings);
        
        // Remove any settings that might conflict with API requirements
        // Clean up roomData to remove any potentially conflicting settings
        $allowedKeys = ['team_id', 'name', 'friendly_url', 'privacy', 'language', 'roles', 'default_role'];
        foreach (array_keys($roomData) as $key) {
            if (!in_array($key, $allowedKeys)) {
                error_log("Removing potentially unsupported field from room request: {$key}");
                unset($roomData[$key]);
            }
        }
        
        // Add meeting title and description if provided
        if (!empty($bookingData['title'])) {
            $roomData['name'] = $bookingData['title'];
        } else {
            $roomData['name'] = 'Meeting with ' . ($provider['display_name'] ?? $provider['username']);
        }
        
        // Log the request data for debugging
        error_log("Digital Samba create room request: " . json_encode($roomData));
        
        // Create the room via Digital Samba API
        try {
            $response = $this->apiRequest('rooms', 'POST', $roomData, $developerKey);
            
            // Log success and return room data
            error_log("Digital Samba room created: " . ($response['id'] ?? 'unknown'));
            
            return $response;
        } catch (\Exception $e) {
            error_log("Failed to create Digital Samba room: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate a participant token for a Digital Samba room
     * 
     * @param string $roomId Digital Samba room ID
     * @param string $developerKey Provider's Digital Samba developer key
     * @param string $name Participant name
     * @param string $role Participant role (moderator, attendee)
     * @param string $participantId External participant ID
     * @param string $teamId Provider's Digital Samba team ID
     * @return array Token data including URL
     * @throws \Exception If token generation fails
     */
    private function generateToken($roomId, $developerKey, $name, $role = 'attendee', $participantId = null, $teamId = null) {
        // Make sure we're using supported roles (moderator, attendee)
        if (!in_array($role, ['moderator', 'attendee'])) {
            $role = 'attendee'; // Default to attendee if an unsupported role is provided
            error_log("Warning: Unsupported role provided. Defaulting to 'attendee'.");
        }
        
        // Map legacy roles to new ones if needed
        if ($role === 'participant') {
            $role = 'attendee';
            error_log("Notice: Converting 'participant' role to 'attendee'");
        }
        
        // Prepare token data
        $tokenData = [
            'u' => $name,
            'role' => $role
        ];
        
        // Add external ID if provided
        if ($participantId) {
            $tokenData['ud'] = $participantId;
        }
        
        // Add team ID if provided (some Digital Samba API endpoints might require this)
        if ($teamId) {
            $tokenData['team_id'] = $teamId;
        }
        
        // Generate token via Digital Samba API
        try {
            $response = $this->apiRequest("rooms/{$roomId}/token", 'POST', $tokenData, $developerKey);
            
            return $response;
        } catch (\Exception $e) {
            error_log("Failed to generate Digital Samba token: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate a meeting link for a single participant using the Digital Samba API
     * 
     * @param array $data Meeting data including provider_id, display_name, etc.
     * @return array Link data
     */
    public function generateMeetingLink($data) {
        try {
            // Extract data
            $providerId = $data['provider_id'] ?? null;
            $displayName = $data['display_name'] ?? 'Unknown User';
            $bookingId = $data['booking_id'] ?? uniqid();
            $role = $data['role'] ?? 'participant';
            $participantId = $data['participant_id'] ?? 'user-' . uniqid();
            
            // Validate required fields
            if (!$providerId) {
                return ['error' => 'Provider ID is required'];
            }
            
            // Get provider details to access their Digital Samba credentials
            $provider = $this->providerModel->findById($providerId);
            
            if (!$provider) {
                return ['error' => 'Provider not found'];
            }
            
            // Check for Digital Samba credentials
            $developerKey = $provider['developer_key'] ?? null;
            $teamId = $provider['team_id'] ?? null;
            
            if (!$developerKey || !$teamId) {
                return [
                    'error' => 'Provider does not have complete Digital Samba credentials',
                    'details' => 'The provider needs both a valid developer_key and team_id in their profile to create meetings.'
                ];
            }
            
            error_log("Found provider credentials - Developer Key: " . substr($developerKey, 0, 10) . "... Team ID: " . $teamId);
            
            // Create or get existing room for this booking
            $booking = $this->bookingModel->getById($bookingId);
            
            $dsRoomId = null;
            
            if ($booking && isset($booking['ds_room_id'])) {
                // Use existing room
                $dsRoomId = $booking['ds_room_id'];
            } else {
                // Create a new room
                $roomData = [
                    'title' => "Meeting with " . ($provider['display_name'] ?? $provider['username'])
                ];
                
                $room = $this->createRoom($providerId, $bookingId, $roomData);
                $dsRoomId = $room['id'] ?? null;
                
                if (!$dsRoomId) {
                    return ['error' => 'Failed to create Digital Samba room'];
                }
                
                // If this is a new room and we have a booking ID, update the booking with room ID
                if ($bookingId && $booking) {
                    $this->bookingModel->update($bookingId, ['ds_room_id' => $dsRoomId]);
                }
            }
            
            // Generate token for participant
            $token = $this->generateToken(
                $dsRoomId,
                $developerKey,
                $displayName,
                $role,
                $participantId,
                $teamId
            );
            
            // Check if the token has a link or url property
            if (!isset($token['url']) && !isset($token['link'])) {
                return ['error' => 'Failed to generate participant token'];
            }
            
            // Use 'link' if available, otherwise fall back to 'url'
            $meetingUrl = $token['link'] ?? $token['url'];
            
            return [
                'success' => true,
                'url' => $meetingUrl,
                'token' => $token['token'] ?? null,
                'ds_room_id' => $dsRoomId,
                'provider_id' => $providerId,
                'display_name' => $displayName,
                'booking_id' => $bookingId
            ];
        } catch (\Exception $e) {
            error_log("Error in DigitalSambaController::generateMeetingLink: " . $e->getMessage());
            return ['error' => 'Failed to generate meeting link: ' . $e->getMessage()];
        }
    }

    /**
     * Generate meeting links for a booking
     * Creates a Digital Samba room and generates access tokens for provider and customer
     */
    public function generateMeetingLinks(string $bookingId) {
        // Keep top-level entry log
        error_log("DigitalSambaController: ENTER generateMeetingLinks for booking ID: " . $bookingId);
        
        try {
            // Get booking
            $booking = $this->bookingModel->getById($bookingId);
            
            if (!$booking) {
                error_log("DS_ERROR: Booking not found: " . $bookingId);
                return ['error' => 'Booking not found', 'status' => 404];
            }
            
            // Get provider details from users collection
            $providerId = (string)$booking['provider_id'];
            $provider = $this->userModel->findById($providerId);
            
            if (!$provider) {
                error_log("DS_ERROR: Provider not found: " . $providerId);
                return ['error' => 'Provider not found', 'status' => 404];
            }
            
            $providerDisplayName = $provider['display_name'] ?? $provider['username'];
            
            // Get customer name from booking
            $customerName = $this->extractCustomerName($booking);
            
            // Check if provider has Digital Samba credentials
            $developerKey = $provider['developer_key'] ?? null;
            $teamId = $provider['team_id'] ?? null;
            
            // If provider has Digital Samba credentials, create a room and tokens
            $providerLink = null;
            $customerLink = null;
            $dsRoomId = null;
            
            if ($developerKey && $teamId) {
                // Keep info log
                error_log("DS_INFO: Provider has credentials. Proceeding with DS API calls.");
                try {
                    // Create or find Digital Samba room
                    $dsRoomId = $booking['ds_room_id'] ?? null;
                    $room = null;
                    
                    if (!$dsRoomId) {
                        // Keep info log
                        error_log("DS_INFO: No existing room ID found. Creating new room...");
                        $room = $this->createRoom(
                            $providerId, 
                            $bookingId,
                            [
                                'title' => "Meeting with {$providerDisplayName} - {$customerName}",
                                'start_time' => $booking['start_time'] ?? null,
                                'end_time' => $booking['end_time'] ?? null
                            ]
                        );
                        $dsRoomId = $room['id'] ?? null;
                        // Keep info log
                        error_log("DS_INFO: createRoom returned. New room ID: " . ($dsRoomId ?? 'null'));
                    } else {
                        // Keep info log
                        error_log("DS_INFO: Using existing room ID: " . $dsRoomId);
                    }
                    
                    if ($dsRoomId) {
                        // Generate provider token (as moderator)
                        $providerToken = $this->generateToken(
                            $dsRoomId,
                            $developerKey,
                            $providerDisplayName,
                            'moderator',
                            'provider-' . $provider['_id'],
                            $teamId
                        );
                        
                        // Generate customer token (as attendee)
                        $customerToken = $this->generateToken(
                            $dsRoomId,
                            $developerKey,
                            $customerName,
                            'attendee',
                            'customer-' . $bookingId,
                            $teamId
                        );
                        
                        // Extract meeting URLs from tokens
                        $providerLink = $providerToken['link'] ?? $providerToken['url'] ?? null;
                        $customerLink = $customerToken['link'] ?? $customerToken['url'] ?? null;
                        // Keep info log
                        error_log("DS_INFO: Extracted links. Provider: " . ($providerLink ? 'OK' : 'FAIL') . ", Customer: " . ($customerLink ? 'OK' : 'FAIL'));
                    } else {
                         // Keep error log
                         error_log("DS_ERROR: Failed to create or find DS Room ID.");
                    }
                } catch (\Exception $e) {
                    // Keep error log
                    error_log("DS_ERROR: Exception during DS API interaction: " . $e->getMessage());
                    // Fall back, links will remain null
                }
            } else {
                // Keep info log
                error_log("DS_INFO: Provider does not have credentials. Skipping DS API calls.");
            }
            
            // If API request failed or provider doesn't have credentials, return error
            if (!$providerLink || !$customerLink) {
                // Keep error log
                error_log("DS_ERROR: Link generation failed or skipped. Returning error array.");
                return [
                    'error' => 'Failed to generate Digital Samba meeting links. Provider may not have proper Digital Samba credentials or an API error occurred.',
                    'details' => 'The provider needs both a valid developer_key and team_id in their profile to create meetings.',
                    'status' => 400 // Or 500 if it was an API error potentially
                ];
            }
            
            // Update booking with links and room ID
            $updateData = [
                'provider_link' => $providerLink,
                //'customer_link' => $customerLink, // Customer link is now nested
                'ds_room_id' => $dsRoomId
            ];
            
            // Update customer data - IMPORTANT: ensure 'customer' field exists and is an array
            if (!isset($booking['customer']) || !is_array($booking['customer'])) {
                 // Keep warning log
                 error_log("DS_WARNING: Booking customer field is missing or not an array. Initializing.");
                 $booking['customer'] = []; // Initialize if missing
            }
            // Preserve existing customer fields and add the link
            $customerDataForUpdate = $booking['customer'];
            $customerDataForUpdate['customer_link'] = $customerLink;
            $customerDataForUpdate['name'] = $customerName; // Ensure name is updated
            
            $updateData['customer'] = $customerDataForUpdate;
            
            try {
                $updated = $this->bookingModel->update($bookingId, $updateData);
                if ($updated) {
                    // Keep info log
                    error_log("DS_INFO: Successfully updated booking ID {$bookingId} with links.");
                } else {
                    // Keep warning log
                    error_log("DS_WARNING: bookingModel->update returned false for ID {$bookingId}. Data might have been the same.");
                    // Consider re-fetching the booking to double-check if links are present
                    $checkBooking = $this->bookingModel->getById($bookingId);
                    if(isset($checkBooking['provider_link']) && isset($checkBooking['customer']['customer_link'])){
                         // Keep info log
                         error_log("DS_INFO: Re-fetch confirmed links are present in booking.");
                         $updated = true; // Treat as success if links are there
                    }
                    else {
                         // Keep error log
                         error_log("DS_ERROR: bookingModel->update returned false AND re-fetch shows links are missing.");
                    }
                }
            } catch (\Throwable $t) {
                // Keep error log
                error_log("DS_ERROR: FATAL ERROR during booking update for ID {$bookingId}: " . $t->getMessage());
                error_log("DS_ERROR: Stack Trace: " . $t->getTraceAsString());
                return ['error' => 'Failed to update booking with links after generation.', 'status' => 500]; 
            }
            
            // Keep final success log
            error_log("DigitalSambaController: Exiting generateMeetingLinks successfully for booking ID: " . $bookingId);
            
            // Return success with links
            // Get updated booking data to include in the response
            $finalBooking = $this->bookingModel->getById($bookingId);
            return [
                'success' => true,
                'message' => 'Meeting links generated successfully',
                'links' => [
                    'provider_link' => $providerLink,
                    'customer_link' => $customerLink
                ],
                'booking' => $finalBooking // Return the latest booking data
            ];
            
        } catch (\Throwable $e) { // Catch Throwable at the top level
            // Keep error log
            error_log("DS_ERROR: Uncaught Throwable in generateMeetingLinks: " . $e->getMessage());
            error_log("DS_ERROR: Trace: " . $e->getTraceAsString());
            return ['error' => 'Failed to generate meeting links: ' . $e->getMessage(), 'status' => 500];
        }
    }
    
    /**
     * Get meeting links for a booking
     */
    public function getMeetingLinks(string $bookingId) {
        try {
            // Get booking
            $booking = $this->bookingModel->getById($bookingId);
            
            if (!$booking) {
                Response::json(['error' => 'Booking not found'], 404);
                return;
            }
            
            // Check if links exist
            $providerLink = $booking['provider_link'] ?? null;
            $customerLink = isset($booking['customer']) && is_array($booking['customer']) ? 
                ($booking['customer']['customer_link'] ?? null) : null;
            
            if (!$providerLink || !$customerLink) {
                // Generate links if they don't exist
                return $this->generateMeetingLinks($bookingId);
            }
            
            // Return existing links
            Response::json([
                'success' => true,
                'links' => [
                    'provider_link' => $providerLink,
                    'customer_link' => $customerLink
                ],
                'ds_room_id' => $booking['ds_room_id'] ?? null,
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            error_log("Error in DigitalSambaController::getMeetingLinks: " . $e->getMessage());
            Response::json(['error' => 'Failed to retrieve meeting links: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Helper method to extract customer name from booking
     * 
     * @param array $booking Booking data
     * @return string Customer name
     */
    private function extractCustomerName($booking) {
        $customerName = '';
        
        if (isset($booking['customer'])) {
            $customerData = $booking['customer'];
            
            // Convert to array if it's an object
            if (is_object($customerData)) {
                $customerData = (array)$customerData;
            }
            
            if (is_array($customerData)) {
                // Get actual customer name from the booking data
                $customerName = $customerData['name'] ?? '';
            } else if (is_string($booking['customer'])) {
                $customerName = $booking['customer'];
            }
        } else if (!empty($booking['customer_name'])) {
            $customerName = $booking['customer_name'];
        }
        
        // Default if we couldn't extract the name
        if (empty($customerName) || $customerName === 'N/A') {
            if (isset($booking['customer_id'])) {
                // Try to get customer info from user model
                try {
                    $userModel = new \App\Models\UserModel();
                    $customer = $userModel->findById((string)$booking['customer_id']);
                    if ($customer) {
                        $customerName = $customer['display_name'] ?? $customer['username'] ?? 'Customer';
                    }
                } catch (\Exception $e) {
                    error_log("Error finding customer: " . $e->getMessage());
                }
            }
            
            if (empty($customerName) || $customerName === 'N/A') {
                $customerName = 'Customer';
            }
        }
        
        return $customerName;
    }
}