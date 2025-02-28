<?php
// This script resets the JWT secret and clears tokens
require_once __DIR__ . '/../config/config.php';

echo "<h1>JWT Reset Utility</h1>";

// Display current secret preview
echo "<p>Current JWT_SECRET preview: " . substr(JWT_SECRET, 0, 3) . "...</p>";

echo "<p>Instructions:</p>";
echo "<ol>";
echo "<li>If you're having JWT validation issues, you need to ensure the same secret is used for both token generation and validation.</li>";
echo "<li>Open your config.php file and find the JWT_SECRET definition.</li>";
echo "<li>Make sure this value matches what was used when the tokens were created.</li>";
echo "<li>If you've changed the secret, all existing tokens will be invalidated.</li>";
echo "<li>After updating the secret, users will need to log in again to get new tokens.</li>";
echo "</ol>";

echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li><a href='test-provider-booking.html'>Go to booking management</a></li>";
echo "<li><a href='jwt_debug.php'>Go to JWT debugger</a></li>";
echo "</ol>";
?>