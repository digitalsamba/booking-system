<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Safely display some info
echo "<h1>JWT Configuration Debug</h1>";

// Check if JWT_SECRET is defined
echo "<h2>JWT Secret Status</h2>";
if (defined('JWT_SECRET')) {
    echo "<p>JWT_SECRET is defined with length: " . strlen(JWT_SECRET) . "</p>";
    echo "<p>First 3 characters: " . htmlspecialchars(substr(JWT_SECRET, 0, 3)) . "...</p>";
} else {
    echo "<p style='color:red'>JWT_SECRET is NOT defined!</p>";
}

// Check JWT class availability
echo "<h2>JWT Library Status</h2>";
if (class_exists('Firebase\JWT\JWT')) {
    echo "<p>Firebase JWT class is properly loaded</p>";
} else {
    echo "<p style='color:red'>Firebase JWT class is NOT available!</p>";
}

// Generate a test token
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

echo "<h2>Test Token Generation</h2>";
try {
    $payload = [
        'user_id' => 'test-user-123',
        'username' => 'tester'
    ];
    
    $issuedAt = time();
    $expiration = $issuedAt + 3600; // 1 hour
    
    $tokenPayload = [
        'iat' => $issuedAt,
        'exp' => $expiration,
        'data' => $payload
    ];
    
    $token = JWT::encode($tokenPayload, JWT_SECRET, 'HS256');
    echo "<p>Token generated successfully: " . htmlspecialchars(substr($token, 0, 20)) . "...</p>";
    
    // Now try to decode the token we just generated
    echo "<h2>Token Validation Test</h2>";
    $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    echo "<p style='color:green'>Token successfully validated!</p>";
    echo "<pre>".htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT))."</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Exception type: " . get_class($e) . "</p>";
}

// Add a test for token from login
echo "<h2>Test Existing Token</h2>";
echo "<form method='post'>
    <textarea name='test_token' rows='10' cols='50' placeholder='Paste your token here'></textarea><br>
    <input type='submit' value='Validate Token'>
</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_token'])) {
    $testToken = trim($_POST['test_token']);
    echo "<p>Testing token: " . htmlspecialchars(substr($testToken, 0, 20)) . "...</p>";
    
    try {
        // Parse the token without verification
        $tokenParts = explode('.', $testToken);
        if (count($tokenParts) === 3) {
            $headerEncoded = $tokenParts[0];
            $payloadEncoded = $tokenParts[1];
            
            $headerJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $headerEncoded));
            $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadEncoded));
            
            echo "<h3>Token Header</h3>";
            echo "<pre>" . htmlspecialchars($headerJson) . "</pre>";
            
            echo "<h3>Token Payload</h3>";
            echo "<pre>" . htmlspecialchars($payloadJson) . "</pre>";
        }
        
        // Try to verify with our current secret
        $decoded = JWT::decode($testToken, new Key(JWT_SECRET, 'HS256'));
        echo "<p style='color:green'>Token successfully validated with current secret!</p>";
        echo "<pre>".htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT))."</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Validation error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Exception type: " . get_class($e) . "</p>";
    }
}
?>