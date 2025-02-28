<?php
// API test script

// Function to make API request
function apiRequest($endpoint, $method = 'GET', $data = null, $headers = []) {
    $url = "http://localhost:8000/$endpoint";
    
    $curl = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    
    // Default content type for JSON data
    $defaultHeaders = [];
    if ($data) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
        $defaultHeaders[] = 'Content-Type: application/json';
    }
    
    // Merge default headers with custom headers
    $allHeaders = array_merge($defaultHeaders, $headers);
    if (!empty($allHeaders)) {
        $options[CURLOPT_HTTPHEADER] = $allHeaders;
    }
    
    curl_setopt_array($curl, $options);
    
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        echo "cURL Error: " . curl_error($curl) . "\n";
    }
    
    curl_close($curl);
    
    // Check if response is valid JSON
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Warning: Invalid JSON response\n";
        echo "Raw response: " . substr($response, 0, 500) . "\n";
        if (strlen($response) > 500) {
            echo "... (response truncated)\n";
        }
    }
    
    return [
        'status' => $status,
        'response' => $decoded,
        'raw_response' => $response
    ];
}

// Test health check
echo "Testing API health...\n";
$result = apiRequest('ping');
echo "Status: {$result['status']}\n";
print_r($result['response']);

// Store credentials for testing
$username = 'testuser' . rand(1000, 9999);
$password = 'password123';
$email = 'test' . rand(1000, 9999) . '@example.com';

// Test user registration
echo "\nRegistering user...\n";
$result = apiRequest('auth/register', 'POST', [
    'username' => $username,
    'email' => $email,
    'password' => $password
]);
echo "Status: {$result['status']}\n";
print_r($result['response']);

// Test user login
echo "\nLogging in...\n";
$result = apiRequest('auth/login', 'POST', [
    'username' => $username,
    'password' => $password
]);
echo "Status: {$result['status']}\n";
print_r($result['response']);

echo "\n=== API Tests Completed ===\n";