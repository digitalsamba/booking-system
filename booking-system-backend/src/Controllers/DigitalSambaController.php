<?php
/**
 * DigitalSambaController
 * 
 * Handles Digital Samba meeting creation and token generation
 * Uses the Digital Samba REST API to create rooms and generate participant tokens
 */

namespace App\Controllers;

use App\Utils\Response;

class DigitalSambaController extends BaseController {
    private $apiBaseUrl;
    private $defaultSettings;
    
    /**
     * Constructor - loads Digital Samba API configuration
     */
    public function __construct() {
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
            CURLOPT_VERBOSE => true
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
        $userModel = new \App\Models\UserModel();
        $provider = $userModel->findById($providerId);
        
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
            $userModel = new \App\Models\UserModel();
            $provider = $userModel->findById($providerId);
            
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
            $bookingModel = new \App\Models\BookingModel();
            $booking = $bookingModel->getById($bookingId);
            
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
                    $bookingModel->update($bookingId, ['ds_room_id' => $dsRoomId]);
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
    public function generateMeetingLinks($bookingId = null) {
        try {
            // Use provided booking ID or extract from request
            if (!$bookingId) {
                // Get booking ID correctly from path info or URL
                $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
                // The booking ID should be the part after 'booking' and before 'meeting-links'
                foreach ($pathParts as $index => $part) {
                    if ($part === 'booking' && isset($pathParts[$index + 1])) {
                        $bookingId = $pathParts[$index + 1];
                        break;
                    }
                }
                
                if (!$bookingId) {
                    error_log("Could not extract booking ID from path: " . ($_SERVER['PATH_INFO'] ?? 'N/A'));
                    Response::json(['error' => 'Booking ID is required'], 400);
                    return;
                }
            }
            
            error_log("Generating meeting links for booking ID: " . $bookingId);
            
            // Get booking
            $bookingModel = new \App\Models\BookingModel();
            $booking = $bookingModel->getById($bookingId);
            
            if (!$booking) {
                Response::json(['error' => 'Booking not found'], 404);
                return;
            }
            
            // Get provider details
            $userModel = new \App\Models\UserModel();
            $provider = $userModel->findById((string)$booking['provider_id']);
            
            if (!$provider) {
                Response::json(['error' => 'Provider not found'], 404);
                return;
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
                try {
                    // Create or find Digital Samba room
                    $dsRoomId = $booking['ds_room_id'] ?? null;
                    $room = null;
                    
                    if (!$dsRoomId) {
                        // Create a new Digital Samba room
                        $room = $this->createRoom(
                            (string)$booking['provider_id'], 
                            $bookingId,
                            [
                                'title' => "Meeting with {$providerDisplayName} - {$customerName}",
                                'start_time' => $booking['start_time'] ?? null,
                                'end_time' => $booking['end_time'] ?? null
                            ]
                        );
                        
                        $dsRoomId = $room['id'] ?? null;
                    }
                    
                    if ($dsRoomId) {
                        // Generate provider token (as moderator)
                        $providerToken = $this->generateToken(
                            $dsRoomId,
                            $developerKey,
                            $providerDisplayName,
                            'moderator', // Use Digital Samba's supported roles
                            'provider-' . $provider['_id'],
                            $teamId
                        );
                        
                        // Generate customer token (as attendee)
                        $customerToken = $this->generateToken(
                            $dsRoomId,
                            $developerKey,
                            $customerName,
                            'attendee', // Use Digital Samba's supported roles (changed from participant)
                            'customer-' . $bookingId,
                            $teamId
                        );
                        
                        // Extract meeting URLs from tokens - Digital Samba API may return 'link' instead of 'url'
                        $providerLink = $providerToken['link'] ?? $providerToken['url'] ?? null;
                        $customerLink = $customerToken['link'] ?? $customerToken['url'] ?? null;
                    }
                } catch (\Exception $e) {
                    error_log("Error creating Digital Samba meeting: " . $e->getMessage());
                    // Fall back to simple links
                }
            }
            
            // If API request failed or provider doesn't have credentials, inform the user
            if (!$providerLink || !$customerLink) {
                Response::json([
                    'error' => 'Failed to generate Digital Samba meeting links. Provider may not have proper Digital Samba credentials.',
                    'details' => 'The provider needs both a valid developer_key and team_id in their profile to create meetings.'
                ], 400);
                return;
            }
            
            // Update booking with links and room ID
            $updateData = [
                'provider_link' => $providerLink,
                'ds_room_id' => $dsRoomId
            ];
            
            // Update customer data
            if (isset($booking['customer']) && is_array($booking['customer'])) {
                // Customer is an object in the booking - preserve all existing fields
                $customer = $booking['customer'];
                $customer['customer_link'] = $customerLink;
                
                // Ensure name is preserved
                if (empty($customer['name']) && !empty($customerName) && $customerName !== 'Customer') {
                    $customer['name'] = $customerName;
                }
                
                $updateData['customer'] = $customer;
            } else {
                // Customer data is missing or not an array - create a minimal record
                $updateData['customer'] = [
                    'id' => $booking['customer_id'] ?? $bookingId,
                    'name' => $customerName,
                    'customer_link' => $customerLink
                ];
            }
            
            // Update the booking
            $success = $bookingModel->update($bookingId, $updateData);
            
            if ($success) {
                // Get updated booking
                $updatedBooking = $bookingModel->getById($bookingId);
                
                Response::json([
                    'success' => true,
                    'message' => 'Meeting links generated successfully',
                    'links' => [
                        'provider_link' => $providerLink,
                        'customer_link' => $customerLink
                    ],
                    'booking' => $updatedBooking
                ]);
            } else {
                Response::json(['error' => 'Failed to update booking with meeting links'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in DigitalSambaController::generateMeetingLinks: " . $e->getMessage());
            Response::json(['error' => 'Failed to generate meeting links: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get meeting links for a booking
     */
    public function getMeetingLinks($bookingId = null) {
        try {
            // Use provided booking ID or extract from request
            if (!$bookingId) {
                // Get booking ID correctly from path info or URL
                $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
                // The booking ID should be the part after 'booking' and before 'meeting-links'
                foreach ($pathParts as $index => $part) {
                    if ($part === 'booking' && isset($pathParts[$index + 1])) {
                        $bookingId = $pathParts[$index + 1];
                        break;
                    }
                }
                
                if (!$bookingId) {
                    error_log("Could not extract booking ID from path: " . ($_SERVER['PATH_INFO'] ?? 'N/A'));
                    Response::json(['error' => 'Booking ID is required'], 400);
                    return;
                }
            }
            
            error_log("Getting meeting links for booking ID: " . $bookingId);
            
            // Get booking
            $bookingModel = new \App\Models\BookingModel();
            $booking = $bookingModel->getById($bookingId);
            
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