<?php
/**
 * Test script for email services
 * 
 * Usage:
 * php email_test.php [recipient] [provider_type]
 * 
 * Example:
 * php email_test.php user@example.com smtp
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load bootstrap file (which loads environment variables)
require __DIR__ . '/bootstrap.php';

// Get parameters from command line
$recipient = $argv[1] ?? null;
$providerType = $argv[2] ?? null;

// Check if recipient is provided
if (!$recipient) {
    echo "Error: Recipient email address is required\n";
    echo "Usage: php email_test.php [recipient] [provider_type]\n";
    exit(1);
}

// Display configuration
echo "Testing email service\n";
echo "-------------------\n";
echo "Recipient: $recipient\n";
echo "Provider: " . ($providerType ?: 'default from .env') . "\n\n";

// Debug: Print SendGrid API key length if specified provider is sendgrid
if ($providerType === 'sendgrid') {
    $apiKey = getenv('SENDGRID_API_KEY');
    echo "SendGrid API Key found in environment: " . (empty($apiKey) ? "No" : "Yes (length: " . strlen($apiKey) . ")") . "\n";
}

try {
    // Create email service
    $emailService = \App\Utils\Email\EmailServiceFactory::create($providerType);
    
    // Check if email service is configured
    if (!$emailService->isConfigured()) {
        echo "Error: Email service is not properly configured. Please check your .env file.\n";
        echo "Provider: " . $emailService->getProviderName() . "\n";
        exit(1);
    }
    
    echo "Using email provider: " . $emailService->getProviderName() . "\n";
    
    // Create and send test email
    $subject = "Booking System Email Test";
    $body = "This is a test email from the Booking System.\n\nTime: " . date('Y-m-d H:i:s');
    $htmlBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { background-color: #3f51b5; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Booking System Email Test</h1>
        </div>
        <div class='content'>
            <p>This is a test email from the Booking System.</p>
            <p>Time: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </body>
    </html>
    ";
    
    // Attempt to send email
    echo "Sending email...\n";
    $success = $emailService->send($recipient, $subject, $body, $htmlBody);
    
    if ($success) {
        echo "Success! Test email sent to $recipient\n";
    } else {
        echo "Error: Failed to send email. Check logs for details.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Create template test if requested
if (isset($argv[3]) && $argv[3] === 'template') {
    echo "\nTesting template-based email...\n";
    
    // Ensure template directory exists
    if (!is_dir(__DIR__ . '/templates/emails')) {
        mkdir(__DIR__ . '/templates/emails', 0777, true);
    }
    
    // Create test template if it doesn't exist
    $templateFile = __DIR__ . '/templates/emails/test_template.php';
    $htmlTemplateFile = __DIR__ . '/templates/emails/test_template_html.php';
    
    if (!file_exists($templateFile)) {
        file_put_contents($templateFile, "Hello <?php echo \$name; ?>,\n\nThis is a test email sent using a template.\n\nRegards,\nBooking System");
    }
    
    if (!file_exists($htmlTemplateFile)) {
        file_put_contents($htmlTemplateFile, "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .header { background-color: #3f51b5; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Template Test</h1>
            </div>
            <div class='content'>
                <p>Hello <strong><?php echo htmlspecialchars(\$name); ?></strong>,</p>
                <p>This is a test email sent using a template.</p>
                <p>Regards,<br>Booking System</p>
            </div>
        </body>
        </html>
        ");
    }
    
    // Send template-based email
    try {
        echo "Sending template-based email...\n";
        $success = $emailService->sendTemplate(
            $recipient,
            "Template Test Email",
            'test_template',
            ['name' => 'Test User']
        );
        
        if ($success) {
            echo "Success! Template-based email sent to $recipient\n";
        } else {
            echo "Error: Failed to send template-based email. Check logs for details.\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} 