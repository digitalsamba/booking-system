<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Header
echo "<h1>JWT Secret Reset Tool</h1>";

// Check if we should update the secret
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_secret') {
        // Get the new secret
        $newSecret = $_POST['new_secret'] ?? 'new-jwt-secret-key-' . bin2hex(random_bytes(8));
        
        // Read the config file
        $configFile = __DIR__ . '/../config/config.php';
        $configContent = file_get_contents($configFile);
        
        if ($configContent === false) {
            echo "<p style='color:red'>Failed to read config file at: $configFile</p>";
        } else {
            // Replace the JWT_SECRET definition
            $pattern = "/define\s*\(\s*['\"]JWT_SECRET['\"]\s*,\s*['\"](.*?)['\"]\s*\)/";
            $replacement = "define('JWT_SECRET', '$newSecret')";
            
            $newContent = preg_replace($pattern, $replacement, $configContent, -1, $count);
            
            if ($count > 0) {
                // Write the updated content back to the file
                if (file_put_contents($configFile, $newContent)) {
                    echo "<p style='color:green'>Successfully updated JWT_SECRET to: " . htmlspecialchars(substr($newSecret, 0, 5)) . "...</p>";
                    echo "<p>Please refresh the page to verify the change.</p>";
                } else {
                    echo "<p style='color:red'>Failed to write updated config file.</p>";
                }
            } else {
                echo "<p style='color:red'>Failed to find JWT_SECRET definition in the config file.</p>";
            }
        }
    } elseif ($_POST['action'] === 'test_token') {
        // Create a test token with the current secret
        $payload = [
            'user_id' => 'test-user-' . time(),
            'username' => 'test-user',
            'role' => 'provider'
        ];
        
        $issuedAt = time();
        $expiration = $issuedAt + 3600;
        
        $tokenPayload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'data' => $payload
        ];
        
        try {
            $token = JWT::encode($tokenPayload, JWT_SECRET, 'HS256');
            
            echo "<h2>Test Token Generated</h2>";
            echo "<p>Token (preview): " . htmlspecialchars(substr($token, 0, 20)) . "...</p>";
            
            // Now verify it
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            
            echo "<p style='color:green'>Token successfully validated with current secret!</p>";
            echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</pre>";
            
            // Display instructions
            echo "<h3>How to Use This Token:</h3>";
            echo "<ol>";
            echo "<li>Copy the full token below</li>";
            echo "<li>In your frontend app, click the 'Get Test Token' button</li>";
            echo "<li>If validation fails, manually set the token by running this in the browser console:</li>";
            echo "<pre>authData.token = '" . htmlspecialchars($token) . "';\nlocalStorage.setItem('authToken', authData.token);</pre>";
            echo "</ol>";
            
            echo "<textarea style='width:100%; height:100px'>" . htmlspecialchars($token) . "</textarea>";
            
        } catch (\Exception $e) {
            echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// Show current JWT_SECRET info
echo "<h2>Current JWT Secret</h2>";
if (defined('JWT_SECRET')) {
    echo "<p>JWT_SECRET is defined with length: " . strlen(JWT_SECRET) . "</p>";
    echo "<p>First few characters: " . htmlspecialchars(substr(JWT_SECRET, 0, 5)) . "...</p>";
} else {
    echo "<p style='color:red'>JWT_SECRET is not defined!</p>";
}

// Form to update the secret
echo "<h2>Update JWT Secret</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='action' value='update_secret'>";
echo "<input type='text' name='new_secret' placeholder='New secret or leave blank for random'>";
echo "<button type='submit'>Update Secret</button>";
echo "</form>";

// Form to test token generation
echo "<h2>Test Token Generation</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='action' value='test_token'>";
echo "<button type='submit'>Generate Test Token</button>";
echo "</form>";
?>