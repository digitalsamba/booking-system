<?php
/**
 * Test script to verify customer details in bookings
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Set up error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

use App\Models\BookingModel;
use App\Controllers\DigitalSambaController;

echo "Testing Booking Customer Details\n";
echo "=============================\n\n";

// Create a test booking with customer details
function testCreateBooking() {
    $bookingModel = new BookingModel();
    
    $testData = [
        'provider_id' => '65fb12345678901234567890', // Replace with a valid provider ID
        'slot_id' => '65fb12345678901234567891',     // Replace with a valid slot ID
        'customer' => [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '123-456-7890'
        ],
        'notes' => 'Test booking with customer details'
    ];
    
    echo "Creating test booking with data:\n";
    echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
    
    $result = $bookingModel->create($testData);
    
    if ($result) {
        $bookingId = (string)$result['_id'];
        echo "Booking created with ID: {$bookingId}\n\n";
        return $bookingId;
    } else {
        echo "Failed to create booking\n";
        return null;
    }
}

// Test retrieving booking
function testGetBooking($bookingId) {
    $bookingModel = new BookingModel();
    
    echo "Retrieving booking with ID: {$bookingId}\n";
    
    $booking = $bookingModel->getById($bookingId);
    
    if ($booking) {
        echo "Retrieved booking data:\n";
        echo json_encode($booking, JSON_PRETTY_PRINT) . "\n\n";
        
        // Check customer data
        if (isset($booking['customer'])) {
            echo "Customer data:\n";
            echo json_encode($booking['customer'], JSON_PRETTY_PRINT) . "\n\n";
            
            $hasName = !empty($booking['customer']['name']) && $booking['customer']['name'] !== 'N/A';
            $hasEmail = !empty($booking['customer']['email']) && $booking['customer']['email'] !== 'N/A';
            
            echo "Customer has name: " . ($hasName ? "Yes" : "No") . "\n";
            echo "Customer has email: " . ($hasEmail ? "Yes" : "No") . "\n\n";
            
            return $hasName && $hasEmail;
        } else {
            echo "No customer data found in booking\n\n";
            return false;
        }
    } else {
        echo "Failed to retrieve booking\n\n";
        return false;
    }
}

// Test generating meeting links with customer name
function testGenerateMeetingLinks($bookingId) {
    $digitalSambaController = new DigitalSambaController();
    
    echo "Generating meeting links for booking ID: {$bookingId}\n";
    
    try {
        // Call the generateMeetingLinks method
        $digitalSambaController->generateMeetingLinks($bookingId);
        
        // Get the booking with links
        $bookingModel = new BookingModel();
        $booking = $bookingModel->getById($bookingId);
        
        if ($booking) {
            echo "Booking with meeting links:\n";
            echo json_encode($booking, JSON_PRETTY_PRINT) . "\n\n";
            
            // Check for customer link
            $hasCustomerLink = isset($booking['customer']['customer_link']) && 
                              !empty($booking['customer']['customer_link']);
            
            echo "Booking has customer link: " . ($hasCustomerLink ? "Yes" : "No") . "\n";
            
            if ($hasCustomerLink) {
                echo "Customer link: " . $booking['customer']['customer_link'] . "\n\n";
                
                // Check if customer name is in the link
                $customerName = $booking['customer']['name'] ?? 'Unknown';
                $linkContainsName = strpos($booking['customer']['customer_link'], urlencode($customerName)) !== false;
                
                echo "Link contains customer name: " . ($linkContainsName ? "Yes" : "No") . "\n\n";
                
                return $linkContainsName;
            } else {
                return false;
            }
        } else {
            echo "Failed to retrieve booking after generating links\n\n";
            return false;
        }
    } catch (Exception $e) {
        echo "Error generating meeting links: " . $e->getMessage() . "\n\n";
        return false;
    }
}

// Run the tests
echo "Starting tests...\n\n";

$bookingId = testCreateBooking();

if ($bookingId) {
    $customerDataOk = testGetBooking($bookingId);
    $meetingLinksOk = testGenerateMeetingLinks($bookingId);
    
    // Report test results
    echo "TEST RESULTS:\n";
    echo "============\n";
    echo "Customer data test: " . ($customerDataOk ? "PASSED" : "FAILED") . "\n";
    echo "Meeting links test: " . ($meetingLinksOk ? "PASSED" : "FAILED") . "\n";
    
    if ($customerDataOk && $meetingLinksOk) {
        echo "\nAll tests PASSED!\n";
    } else {
        echo "\nSome tests FAILED!\n";
    }
} else {
    echo "Could not run tests without a valid booking ID\n";
}