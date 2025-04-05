<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up autoloading via Composer
require_once __DIR__ . '/vendor/autoload.php';

// Load necessary files
require_once __DIR__ . '/src/Utils/Email/EmailConfig.php';
require_once __DIR__ . '/src/Utils/Email/EmailService.php';
require_once __DIR__ . '/src/Utils/Email/EmailServiceFactory.php';
require_once __DIR__ . '/src/Utils/Email/Providers/SendgridEmailProvider.php';

use App\Utils\Email\EmailConfig;
use App\Utils\Email\EmailServiceFactory;

// Log where we're starting from
echo "Current directory: " . getcwd() . "\n";
echo ".env file exists: " . (file_exists('.env') ? 'Yes' : 'No') . "\n\n";

// Check command line args
if ($argc < 2) {
    echo "Usage: php email_service_test.php <to_email> [provider] [template_id]\n";
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

// Create email service using factory
try {
    echo "Creating email service with provider: " . ($provider ?? "default from config") . "\n";
    $emailService = EmailServiceFactory::create($provider);
    echo "Email service created successfully\n\n";
    
    // If template ID provided, send template email
    if ($templateId) {
        echo "Sending template email...\n";
        
        // Template data
        $templateData = [
            'name' => 'Test User',
            'booking_date' => date('Y-m-d H:i:s'),
            'booking_id' => uniqid(),
            'service' => 'Test Service'
        ];
        
        // Send email
        $success = $emailService->sendTemplateEmail(
            $toEmail,
            $templateId,
            $templateData
        );
    } else {
        echo "Sending regular email...\n";
        
        // Send regular email
        $success = $emailService->sendEmail(
            $toEmail,
            'Test Email from Booking System',
            "This is a test email sent from the Booking System.\n\nThis email was sent at " . date('Y-m-d H:i:s'),
            "<h1>Test Email from Booking System</h1><p>This is a test email sent from the Booking System.</p><p>This email was sent at " . date('Y-m-d H:i:s') . "</p>"
        );
    }
    
    // Check result
    if ($success) {
        echo "Email sent successfully!\n";
    } else {
        echo "Failed to send email\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete.\n"; 