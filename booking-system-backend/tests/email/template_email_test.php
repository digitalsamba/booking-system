<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up autoloading via Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Load our email config class
require_once __DIR__ . '/../../src/Utils/Email/EmailConfig.php';
require_once __DIR__ . '/../../src/Utils/Email/EmailService.php';
require_once __DIR__ . '/../../src/Utils/Email/EmailServiceFactory.php';
require_once __DIR__ . '/../../src/Utils/Email/Providers/SendgridEmailProvider.php';

use App\Utils\Email\EmailConfig;
use App\Utils\Email\EmailServiceFactory;

// Set root directory
$rootDir = __DIR__ . '/../..';

// Log where we're starting from
echo "Current directory: " . getcwd() . "\n";
echo ".env file exists: " . (file_exists($rootDir . '/.env') ? 'Yes' : 'No') . "\n\n";

// Check command line args
if ($argc < 2) {
    echo "Usage: php template_email_test.php <to_email> [provider]\n";
    echo "  to_email    - recipient email address\n";
    echo "  provider    - optional, email provider to use (default: from .env)\n";
    exit(1);
}

// Parse command line arguments
$toEmail = $argv[1];
$provider = $argc > 2 ? $argv[2] : null;

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
    
    // Prepare template data for booking confirmation
    $templateData = [
        'customer_name' => 'Test Customer',
        'provider_name' => 'Test Provider',
        'booking_date' => date('l, F j, Y'),
        'start_time' => date('h:i A', strtotime('+1 hour')),
        'end_time' => date('h:i A', strtotime('+2 hours')),
        'booking_id' => 'BOOK-' . strtoupper(substr(md5(time()), 0, 8)),
        'customer_link' => 'https://meet.example.com/test-meeting',
        'notes' => "This is a test booking confirmation.\nPlease arrive 5 minutes early.",
        'company_name' => 'Booking System'
    ];
    
    echo "Preparing email with template data...\n";
    
    // Render the template
    $templateFile = $rootDir . '/templates/emails/booking_confirmation_html.php';
    if (!file_exists($templateFile)) {
        throw new Exception("Template file not found: $templateFile");
    }
    
    // Extract template data to variables
    extract($templateData);
    
    // Capture template output
    ob_start();
    include $templateFile;
    $htmlBody = ob_get_clean();
    
    // Create plain text version
    $textBody = "Booking Confirmation\n\n" .
                "Dear {$customer_name},\n\n" .
                "Your booking with {$provider_name} has been confirmed.\n\n" .
                "Booking Details:\n" .
                "Date: {$booking_date}\n" .
                "Time: {$start_time} - {$end_time}\n" .
                "Booking ID: {$booking_id}\n\n" .
                "Thank you for using our booking system.\n\n" .
                "Regards,\n" .
                $company_name;
    
    echo "Sending email using the template...\n";
    
    // If using SendGrid, we'll use direct cURL for better error handling
    if (strtolower($provider) === 'sendgrid') {
        $apiKey = EmailConfig::get('SENDGRID_API_KEY');
        $fromEmail = EmailConfig::get('EMAIL_FROM', 'noreply@example.com');
        $fromName = EmailConfig::get('EMAIL_FROM_NAME', 'Booking System');
        
        if (empty($apiKey)) {
            throw new Exception("SendGrid API key not found in configuration");
        }
        
        // Build email payload
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $toEmail]
                    ],
                    'subject' => 'Your Booking Confirmation'
                ]
            ],
            'from' => [
                'email' => $fromEmail,
                'name' => $fromName
            ],
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $textBody
                ],
                [
                    'type' => 'text/html',
                    'value' => $htmlBody
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
        $appEnv = EmailConfig::get('APP_ENV', 'production');
        if ($appEnv === 'development') {
            echo "Disabling SSL verification for development...\n";
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        
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
        // Send using the EmailService
        $success = $emailService->sendEmail(
            $toEmail,
            'Your Booking Confirmation',
            $textBody,
            $htmlBody
        );
        
        // Check result
        if ($success) {
            echo "Email sent successfully!\n";
        } else {
            echo "Failed to send email\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete.\n"; 