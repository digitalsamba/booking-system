<?php
/**
 * DigitalSambaController
 * 
 * Handles Digital Samba meeting link generation and management
 */

namespace App\Controllers;

use App\Utils\Response;

class DigitalSambaController extends BaseController {
    /**
     * Generate meeting link for a meeting participant
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
            $customerName = $data['customer_name'] ?? 'Customer';
            $startTime = $data['start_time'] ?? null;
            $endTime = $data['end_time'] ?? null;
            
            // Validate required fields
            if (!$providerId) {
                return ['error' => 'Provider ID is required'];
            }
            
            // Format meeting link with the display name
            $url = "https://meetings.digitalsamba.com/{$providerId}/{$bookingId}?name=" . urlencode($displayName);
            
            // Add time parameters if provided
            if ($startTime && $endTime) {
                $url .= "&start_time=" . urlencode($startTime) . "&end_time=" . urlencode($endTime);
            }
            
            return [
                'success' => true,
                'url' => $url,
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
            $providerDisplayName = $provider ? ($provider['display_name'] ?? $provider['username']) : 'Provider';
            
            // Get customer name from booking
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
                    
                    // Additional debugging
                    error_log("Customer data in booking: " . json_encode($customerData));
                } else {
                    error_log("Customer field exists but is not an array or object: " . gettype($booking['customer']));
                    
                    // If it's a string, use it directly
                    if (is_string($booking['customer'])) {
                        $customerName = $booking['customer'];
                    }
                }
            } else {
                // Check for flat fields
                if (!empty($booking['customer_name'])) {
                    $customerName = $booking['customer_name'];
                    error_log("Using customer_name from flat field: " . $customerName);
                } else {
                    error_log("No customer field in booking data");
                }
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
                            error_log("Found customer name from user record: " . $customerName);
                        }
                    } catch (\Exception $e) {
                        error_log("Error finding customer: " . $e->getMessage());
                    }
                }
                
                if (empty($customerName) || $customerName === 'N/A') {
                    error_log("Could not find customer name, using default");
                    $customerName = 'Customer';
                }
            }
            
            // Log the customer name for debugging
            error_log("Using customer name for meeting link: " . $customerName);
            
            // Generate links with display names
            $providerLink = "https://DS/{$bookingId}/provider?display_name=" . urlencode($providerDisplayName);
            $customerLink = "https://DS/{$bookingId}/customer?display_name=" . urlencode($customerName);
            
            // Update booking with links
            $updateData = [
                'provider_link' => $providerLink
            ];
            
            // Check if customer is an array or just an ID
            if (isset($booking['customer']) && is_array($booking['customer'])) {
                // Customer is an object in the booking - preserve all existing fields
                $customer = $booking['customer'];
                $customer['customer_link'] = $customerLink;
                
                // Ensure name and email are preserved
                if (empty($customer['name']) && !empty($customerName) && $customerName !== 'Customer') {
                    $customer['name'] = $customerName;
                    error_log("Adding missing customer name from detected value: " . $customerName);
                }
                
                $updateData['customer'] = $customer;
                error_log("Updating booking with customer data: " . json_encode($customer));
            } else {
                // Customer data is missing or not an array - create a minimal record
                $updateData['customer'] = [
                    'id' => $booking['customer_id'] ?? $bookingId,
                    'name' => $customerName,
                    'customer_link' => $customerLink
                ];
                error_log("Creating new customer data structure: " . json_encode($updateData['customer']));
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
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            error_log("Error in DigitalSambaController::getMeetingLinks: " . $e->getMessage());
            Response::json(['error' => 'Failed to retrieve meeting links: ' . $e->getMessage()], 500);
        }
    }
}