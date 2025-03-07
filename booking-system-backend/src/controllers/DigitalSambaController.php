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
                    $this->error('Booking ID is required', 400);
                    return;
                }
            }
            
            error_log("Generating meeting links for booking ID: " . $bookingId);
            
            // Get booking
            $bookingModel = new \App\Models\BookingModel();
            $booking = $bookingModel->getById($bookingId);
            
            if (!$booking) {
                $this->error('Booking not found', 404);
                return;
            }
            
            // Generate simple dummy links
            $providerLink = "https://DS/{$bookingId}/provider";
            $customerLink = "https://DS/{$bookingId}/customer";
            
            // Update booking with links
            $updateData = [
                'provider_link' => $providerLink
            ];
            
            // Check if customer is an array or just an ID
            if (isset($booking['customer']) && is_array($booking['customer'])) {
                // Customer is an object in the booking
                $customer = $booking['customer'];
                $customer['customer_link'] = $customerLink;
                $updateData['customer'] = $customer;
            } else {
                // Customer is just referenced by ID
                $updateData['customer'] = [
                    'id' => $booking['customer_id'] ?? $bookingId,
                    'customer_link' => $customerLink
                ];
            }
            
            // Update the booking
            $success = $bookingModel->update($bookingId, $updateData);
            
            if ($success) {
                // Get updated booking
                $updatedBooking = $bookingModel->getById($bookingId);
                
                $this->success([
                    'message' => 'Meeting links generated successfully',
                    'links' => [
                        'provider_link' => $providerLink,
                        'customer_link' => $customerLink
                    ],
                    'booking' => $updatedBooking
                ]);
            } else {
                $this->error('Failed to update booking with meeting links', 500);
            }
        } catch (\Exception $e) {
            error_log("Error in DigitalSambaController::generateMeetingLinks: " . $e->getMessage());
            $this->error('Failed to generate meeting links', 500, ['details' => $e->getMessage()]);
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
                    $this->error('Booking ID is required', 400);
                    return;
                }
            }
            
            error_log("Getting meeting links for booking ID: " . $bookingId);
            
            // Get booking
            $bookingModel = new \App\Models\BookingModel();
            $booking = $bookingModel->getById($bookingId);
            
            if (!$booking) {
                $this->error('Booking not found', 404);
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