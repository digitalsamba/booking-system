<?php
// booking_test.php - Test booking functionality

// Function to make API request
function apiRequest($endpoint, $method = 'GET', $data = null, $headers = []) {
    // API request implementation (same as before)
}

// Get credentials for testing
$username = 'admin';
$password = 'admin123';

// Login to get token
echo "Logging in as admin...\n";
$result = apiRequest('auth/login', 'POST', [
    'username' => $username,
    'password' => $password
]);

if (!isset($result['response']['token'])) {
    echo "Failed to login!\n";
    exit;
}

$token = $result['response']['token'];
$userId = $result['response']['user']['id'];
echo "Logged in with user ID: $userId\n";

// Set availability slots
echo "\nCreating availability slots...\n";
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$slots = [];

for ($hour = 9; $hour <= 16; $hour++) {
    $slots[] = [
        'date' => $tomorrow,
        'start_time' => sprintf('%02d:00:00', $hour),
        'end_time' => sprintf('%02d:00:00', $hour + 1),
        'is_available' => true
    ];
}

$result = apiRequest('availability/setSlots', 'POST', [
    'slots' => $slots
], [
    'Authorization: Bearer ' . $token
]);

echo "Status: {$result['status']}\n";
print_r($result['response']);

// Get availability slots
echo "\nGetting availability slots...\n";
$result = apiRequest("availability/getSlots?user_id=$userId&start_date=$tomorrow&end_date=$tomorrow");
echo "Status: {$result['status']}\n";
print_r($result['response']);

// Create a booking
echo "\nCreating a booking...\n";
$result = apiRequest('booking/create', 'POST', [
    'user_id' => $userId,
    'visitor_name' => 'John Doe',
    'visitor_email' => 'john@example.com',
    'start_time' => "$tomorrow 10:00:00",
    'end_time' => "$tomorrow 11:00:00",
    'notes' => 'Test booking'
]);

echo "Status: {$result['status']}\n";
print_r($result['response']);

// Get bookings
echo "\nGetting bookings...\n";
$result = apiRequest("booking/getBookings?user_id=$userId&start_date=$tomorrow&end_date=$tomorrow");
echo "Status: {$result['status']}\n";
print_r($result['response']);

echo "\n=== Booking Tests Completed ===\n";