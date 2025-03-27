<?php
// Debug endpoint to help diagnose authentication issues

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Check if getallheaders function exists
$getallheadersExists = function_exists('getallheaders') ? 'yes' : 'no';

// Try to get headers using different methods
$headers = [];

// Method 1: getallheaders (if available)
if (function_exists('getallheaders')) {
    $headers['getallheaders'] = getallheaders();
}

// Method 2: apache_request_headers (if available)
if (function_exists('apache_request_headers')) {
    $headers['apache_request_headers'] = apache_request_headers();
}

// Method 3: $_SERVER variables
$serverHeaders = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
        $serverHeaders[$name] = $value;
    }
}
$headers['server_variables'] = $serverHeaders;

// Specific check for Authorization headers
$authHeaders = [
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'not set',
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'not set',
    'HTTP_AUTH' => $_SERVER['HTTP_AUTH'] ?? 'not set',
    'PHP_AUTH_USER' => $_SERVER['PHP_AUTH_USER'] ?? 'not set',
    'PHP_AUTH_PW' => $_SERVER['PHP_AUTH_PW'] ? 'set (not shown)' : 'not set',
    'PHP_AUTH_DIGEST' => $_SERVER['PHP_AUTH_DIGEST'] ?? 'not set',
];

// Check for Authorization in all headers
$authorizationFound = false;
$authorizationHeader = null;

if (isset($headers['getallheaders'])) {
    foreach ($headers['getallheaders'] as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authorizationFound = true;
            $authorizationHeader = $value;
            break;
        }
    }
}

if (!$authorizationFound && isset($headers['apache_request_headers'])) {
    foreach ($headers['apache_request_headers'] as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authorizationFound = true;
            $authorizationHeader = $value;
            break;
        }
    }
}

// Output the debug information
echo json_encode([
    'getallheaders_function_exists' => $getallheadersExists,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'headers' => $headers,
    'auth_specific_headers' => $authHeaders,
    'authorization_found' => $authorizationFound,
    'authorization_header' => $authorizationHeader ? substr($authorizationHeader, 0, 20) . '...' : null,
    'server_variables' => array_slice($_SERVER, 0, 20), // Just show first 20 to avoid too much output
    'timestamp' => time()
], JSON_PRETTY_PRINT);