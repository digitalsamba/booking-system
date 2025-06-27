<?php
/**
 * Test script to directly test SendGrid email sending
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Utils/Email/EmailConfig.php';
require_once __DIR__ . '/src/Utils/Email/EmailService.php';
require_once __DIR__ . '/src/Utils/Email/EmailServiceFactory.php';
require_once __DIR__ . '/src/Utils/Email/Providers/SendgridEmailProvider.php';

use App\Utils\Email\EmailConfig;
use App\Utils\Email\Providers\SendgridEmailProvider;

if (count($argv) < 2) {
    echo "Usage: php sendgrid_direct_test.php [recipient_email]\n";
    exit(1);
}

$recipientEmail = $argv[1];

// Force direct API key loading from .env file
$provider = new SendgridEmailProvider();

echo "SendGrid provider initialized\n";
echo "API key length: " . strlen($provider->getApiKey()) . "\n";

// Testing configuration
echo "Provider configured: " . ($provider->isConfigured() ? "YES" : "NO") . "\n";

// Set up a simple test email
$subject = 'Test Email from SendGrid Direct Test';
$textBody = "This is a test email sent at " . date('Y-m-d H:i:s') . " using direct SendGrid API.\n\nIf you receive this, the SendGrid integration is working correctly.";
$htmlBody = "<html><body><h1>Test Email</h1><p>This is a test email sent at " . date('Y-m-d H:i:s') . " using direct SendGrid API.</p><p>If you receive this, the SendGrid integration is working correctly.</p></body></html>";

// Attempt to send
echo "Attempting to send test email to $recipientEmail...\n";
$result = $provider->sendEmail($recipientEmail, $subject, $textBody, $htmlBody);

echo "Send result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
