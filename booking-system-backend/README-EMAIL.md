# Email Functionality in Booking System

This document explains how to set up and use the email functionality in the Booking System backend.

## Configuration

Email settings are configured in the `.env` file in the project root. The following settings are available:

```
# Email provider (smtp, sendgrid, ses)
EMAIL_PROVIDER=sendgrid

# Default sender
EMAIL_FROM=bookings@example.com
EMAIL_FROM_NAME="Booking System"

# SMTP settings (for SMTP provider)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=username
SMTP_PASSWORD=password
SMTP_ENCRYPTION=tls  # tls, ssl, null

# SendGrid settings (for SendGrid provider)
SENDGRID_API_KEY=your_sendgrid_api_key

# Amazon SES settings (for SES provider)
SES_KEY=your_aws_key
SES_SECRET=your_aws_secret
SES_REGION=us-east-1
```

## Available Email Providers

The system supports the following email providers:

1. **SMTP** - Standard SMTP server
2. **SendGrid** - [SendGrid](https://sendgrid.com/) API
3. **Amazon SES** - [Amazon Simple Email Service](https://aws.amazon.com/ses/)

## Basic Usage

To send emails from your application code:

```php
// Import necessary classes
use App\Utils\Email\EmailServiceFactory;

// Create email service using factory (uses provider from .env)
$emailService = EmailServiceFactory::create();

// Send a simple email
$success = $emailService->sendEmail(
    'recipient@example.com',
    'Email Subject',
    'This is the plain text body',
    '<h1>This is the HTML body</h1><p>With formatted content.</p>',
    // Optional parameters:
    'sender@example.com',      // From email (optional)
    'Sender Name',             // From name (optional)
    'reply-to@example.com'     // Reply-to email (optional)
);

// Check if email was sent successfully
if ($success) {
    // Email sent
} else {
    // Email failed
}
```

## Sending Template Emails

For SendGrid, you can use dynamic templates:

```php
// Create email service
$emailService = EmailServiceFactory::create('sendgrid');

// Template data
$templateData = [
    'name' => 'John Doe',
    'booking_date' => '2023-05-15 14:30',
    'service' => 'Haircut'
];

// Send email with template
$success = $emailService->sendTemplateEmail(
    'recipient@example.com',
    'd-f3b4a7c8e9d0f1b2a3c4d5e6f7g8h9i0',  // SendGrid template ID
    $templateData
);
```

## Using PHP Templates

The system also supports PHP-based templates located in the `templates/emails` directory.

Here's an example of using a template:

```php
// Prepare data for the template
$templateData = [
    'customer_name' => 'John Doe',
    'provider_name' => 'Dr. Smith',
    'booking_date' => 'Monday, April 15, 2024',
    'start_time' => '09:00 AM',
    'end_time' => '10:00 AM',
    'booking_id' => 'BOOK-12345',
    'customer_link' => 'https://meet.example.com/room',
    'notes' => 'Please bring your ID.',
    'company_name' => 'Medical Center'
];

// Extract variables for the template
extract($templateData);

// Render the template
ob_start();
include __DIR__ . '/templates/emails/booking_confirmation_html.php';
$htmlBody = ob_get_clean();

// Create plain text version
$textBody = "Booking Confirmation\n\n" .
            "Dear {$customer_name},\n\n" .
            "Your booking has been confirmed.\n" .
            // ... more text ...
```

## Testing Email Functionality

You can use the provided test scripts to verify your email configuration:

1. **Basic test** with direct API call:
   ```
   cd tests/email
   php email_direct_test.php recipient@example.com sendgrid
   ```

2. **Service test** using EmailServiceFactory:
   ```
   cd tests/email
   php email_service_test.php recipient@example.com sendgrid
   ```

3. **Template test** using PHP templates:
   ```
   cd tests/email
   php template_email_test.php recipient@example.com sendgrid
   ```

4. **Dynamic template test** (SendGrid):
   ```
   cd tests/email
   php email_service_test.php recipient@example.com sendgrid d-yourtemplateid
   ```

## SSL Certificate Issues in Development

If you encounter SSL certificate issues in development:

1. The system automatically disables SSL verification in development mode if `APP_ENV=development` in your `.env` file.
2. For production, ensure proper SSL certificates are set up.

## Troubleshooting

1. **Emails not being sent**:
   - Check your `.env` configuration
   - Verify API keys or credentials
   - Check server logs for error messages

2. **SSL/TLS errors**:
   - Make sure `APP_ENV=development` in `.env` for local testing
   - Ensure proper certificates in production

3. **API rate limiting**:
   - SendGrid and other providers have rate limits
   - Consider implementing retry logic for important emails 