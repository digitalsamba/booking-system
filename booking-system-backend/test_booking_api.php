<?php
/**
 * Test script for booking API
 * 
 * Usage: php test_booking_api.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Create a test booking request
$testData = [
    'provider_username' => 'conal1', // Replace with an actual username from your system
    'slot_id' => '6571a1234567890', // Replace with a valid slot ID from your system
    'customer' => [
        'name' => 'Test Customer',
        'email' => 'test@example.com'
    ],
    'notes' => 'This is a test booking from the API test script.'
];

// Convert to JSON
$jsonData = json_encode($testData);

// Create cURL request
$ch = curl_init('http://localhost:8000/public/booking');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);

// Execute request
echo "Sending test booking request...\n";
echo "Request data: " . $jsonData . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Display results
echo "Response code: " . $httpCode . "\n";
echo "Response body: " . $response . "\n";

// Parse response
$responseData = json_decode($response, true);

// Check if successful
if ($httpCode >= 200 && $httpCode < 300) {
    echo "\nSUCCESS: Booking created successfully!\n";
    if (isset($responseData['booking_id'])) {
        echo "Booking ID: " . $responseData['booking_id'] . "\n";
    }
} else {
    echo "\nERROR: Failed to create booking.\n";
    if (isset($responseData['error'])) {
        echo "Error message: " . $responseData['error'] . "\n";
    }
} 