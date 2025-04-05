<?php
/**
 * Test script for BookingConfirmationEmail in PublicController
 * 
 * Usage: php test_booking_confirmation.php recipient@example.com
 */

// Include autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Include bootstrap to load configuration
if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define PublicController if it doesn't exist
if (!class_exists('App\Controllers\PublicController')) {
    echo "Loading PublicController class...\n";
    require_once __DIR__ . '/src/Controllers/BaseController.php';
    require_once __DIR__ . '/src/Models/UserModel.php';
    require_once __DIR__ . '/src/Models/AvailabilityModel.php';
    require_once __DIR__ . '/src/Models/BookingModel.php';
    require_once __DIR__ . '/src/Controllers/PublicController.php';
}

use App\Controllers\PublicController;

// Get recipient email from command line
$recipientEmail = $argv[1] ?? null;
if (!$recipientEmail) {
    echo "Usage: php test_booking_confirmation.php recipient@example.com\n";
    exit(1);
}

echo "Testing BookingConfirmationEmail for $recipientEmail\n\n";

// Create PublicController instance
$publicController = new PublicController();

// Use reflection to access private method
$reflectionClass = new ReflectionClass(PublicController::class);
$method = $reflectionClass->getMethod('sendBookingConfirmationEmail');
$method->setAccessible(true);

// Prepare test data
$customerName = 'Test Customer';
$providerName = 'Test Provider';
$bookingData = [
    'booking_date' => date('l, F j, Y'),
    'start_time' => date('g:i A', strtotime('+1 hour')),
    'end_time' => date('g:i A', strtotime('+2 hours')),
    'booking_id' => 'TEST' . rand(1000, 9999),
    'notes' => 'This is a test of the booking confirmation email system.',
    'customer_link' => 'https://meet.google.com/test-meeting-link',
    'company_name' => 'SambaConnect'
];

echo "Sending test email with the following data:\n";
echo "- Customer: $customerName\n";
echo "- Provider: $providerName\n";
echo "- Date: {$bookingData['booking_date']}\n";
echo "- Time: {$bookingData['start_time']} - {$bookingData['end_time']}\n\n";

try {
    // Call the method
    $result = $method->invoke($publicController, $recipientEmail, $customerName, $providerName, $bookingData);
    
    if ($result) {
        echo "SUCCESS: Email sent successfully to $recipientEmail\n";
    } else {
        echo "ERROR: Failed to send email to $recipientEmail\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "STACK TRACE:\n" . $e->getTraceAsString() . "\n";
} 