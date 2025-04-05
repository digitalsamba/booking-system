<?php
/**
 * Direct test for SendGrid email
 * 
 * This script doesn't rely on environment variables
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up autoloading via Composer
require_once __DIR__ . '/vendor/autoload.php';

// Load our email config class
require_once __DIR__ . '/src/Utils/Email/EmailConfig.php';

use App\Utils\Email\EmailConfig;

// Log where we're starting from
echo "Current directory: " . getcwd() . "\n";
echo ".env file exists: " . (file_exists('.env') ? 'Yes' : 'No') . "\n\n";

// Check command line args
if ($argc < 2) {
    echo "Usage: php email_direct_test.php <to_email> [provider] [template_id]\n";
    echo "  to_email    - recipient email address\n";
    echo "  provider    - optional, email provider to use (default: from .env)\n";
    echo "  template_id - optional, template ID for template emails\n";
    exit(1);
}

// Parse command line arguments
$toEmail = $argv[1];
$provider = $argc > 2 ? $argv[2] : null;
$templateId = $argc > 3 ? $argv[3] : null;

// Load configuration
EmailConfig::load();

// Show available config
echo "Available config:\n";
foreach (EmailConfig::all() as $key => $value) {
    // Don't show full API keys in output
    if (strpos($key, 'API_KEY') !== false && !empty($value)) {
        echo "$key = " . substr($value, 0, 5) . '...' . substr($value, -5) . " (length: " . strlen($value) . ")\n";
    } else {
        echo "$key = $value\n";
    }
}
echo "\n";

// Use explicit SendGrid API without the PHP SDK
if (strtolower($provider) === 'sendgrid') {
    echo "Testing SendGrid direct API call...\n";
    
    // Get config values
    $apiKey = EmailConfig::get('SENDGRID_API_KEY');
    $fromEmail = EmailConfig::get('EMAIL_FROM', 'noreply@example.com');
    $fromName = EmailConfig::get('EMAIL_FROM_NAME', 'Booking System');
    
    if (empty($apiKey)) {
        echo "Error: SendGrid API key not found in configuration\n";
        exit(1);
    }
    
    // Build email payload
    $data = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $toEmail]
                ],
                'subject' => 'Test Email from Booking System'
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => "This is a test email sent from the Booking System.\n\nThis email was sent at " . date('Y-m-d H:i:s')
            ],
            [
                'type' => 'text/html',
                'value' => "<h1>Test Email from Booking System</h1><p>This is a test email sent from the Booking System.</p><p>This email was sent at " . date('Y-m-d H:i:s') . "</p>"
            ]
        ]
    ];
    
    // Send email via SendGrid API
    echo "Sending email via SendGrid API...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Disable SSL verification for development
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $httpCode = $info['http_code'];
    
    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
    }
    
    curl_close($ch);
    
    echo "HTTP Status: " . $httpCode . "\n";
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Email sent successfully!\n";
    } else {
        echo "Error sending email. Response: " . $response . "\n";
    }
} else {
    echo "Skipping direct API test for provider: " . ($provider ?? "none") . "\n";
}

echo "\nTest complete.\n"; 