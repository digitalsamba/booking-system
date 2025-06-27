<?php
/**
 * Test script for booking confirmation emails
 * 
 * Usage: php test_booking_email.php recipient@example.com
 */

// Include autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Include bootstrap to load configuration
if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
}

use App\Utils\Email\EmailServiceFactory;
use App\Utils\Email\EmailConfig;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get recipient email from command line
$recipientEmail = $argv[1] ?? null;
if (!$recipientEmail) {
    echo "Usage: php test_booking_email.php recipient@example.com\n";
    exit(1);
}

// Check if all required ENV variables are set
echo "Checking email configuration...\n";
$allConfig = EmailConfig::all();
echo "Found " . count($allConfig) . " configuration variables\n";

$requiredVars = [
    'EMAIL_PROVIDER',
    'EMAIL_FROM',
    'EMAIL_FROM_NAME'
];

$providerSpecificVars = [
    'smtp' => ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_ENCRYPTION'],
    'sendgrid' => ['SENDGRID_API_KEY'],
    'ses' => ['SES_KEY', 'SES_SECRET', 'SES_REGION']
];

$provider = EmailConfig::get('EMAIL_PROVIDER') ?: 'smtp';
echo "Email provider: $provider\n";

foreach ($requiredVars as $var) {
    $value = EmailConfig::get($var);
    echo "$var: " . ($value ? "✓" : "✗") . "\n";
}

if (isset($providerSpecificVars[$provider])) {
    foreach ($providerSpecificVars[$provider] as $var) {
        $value = EmailConfig::get($var);
        echo "$var: " . ($value ? "✓" : "✗") . "\n";
    }
}

// Try to send a test email
try {
    echo "\nCreating email service...\n";
    $emailService = EmailServiceFactory::create();
    echo "Email service created successfully\n";
    
    // Prepare template data
    $templateData = [
        'customer_name' => 'Test User',
        'provider_name' => 'Test Provider',
        'booking_date' => date('l, F j, Y'),
        'start_time' => date('g:i A', strtotime('+1 hour')),
        'end_time' => date('g:i A', strtotime('+2 hours')),
        'booking_id' => 'TEST' . rand(1000, 9999),
        'customer_link' => 'https://meet.google.com/test-meeting-link',
        'notes' => 'This is a test booking confirmation email.',
        'company_name' => 'SambaConnect'
    ];
    
    // Get template path
    $templateFile = __DIR__ . '/templates/emails/booking_confirmation_html.php';
    echo "Using template file: $templateFile\n";
    
    // Check if template exists
    if (!file_exists($templateFile)) {
        echo "ERROR: Template not found at $templateFile\n";
        exit(1);
    }
    echo "Template file exists\n";
    
    // Extract data for template
    extract($templateData);
    
    // Render template
    echo "Rendering HTML template...\n";
    ob_start();
    include $templateFile;
    $htmlBody = ob_get_clean();
    echo "HTML template rendered. Length: " . strlen($htmlBody) . "\n";
    
    // Create plain text version
    $textBody = "Booking Confirmation\n\n" .
                "Dear {$customer_name},\n\n" .
                "Your booking with {$provider_name} has been confirmed.\n\n" .
                "Booking Details:\n" .
                "Date: {$booking_date}\n" .
                "Time: {$start_time} - {$end_time}\n" .
                "Booking ID: {$booking_id}\n\n";
    
    if (!empty($customer_link)) {
        $textBody .= "Meeting Link: {$customer_link}\n\n";
    }
    
    if (!empty($notes)) {
        $textBody .= "Additional Notes:\n{$notes}\n\n";
    }
    
    $textBody .= "Thank you for using our booking system.\n\n" .
                "Regards,\n" .
                $company_name;
    
    // Send email
    echo "Attempting to send email to $recipientEmail...\n";
    $success = $emailService->sendEmail(
        $recipientEmail,
        'Your Test Booking Confirmation',
        $textBody,
        $htmlBody
    );
    
    if ($success) {
        echo "SUCCESS: Test email sent successfully to $recipientEmail\n";
    } else {
        echo "ERROR: Failed to send test email to $recipientEmail\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
} 