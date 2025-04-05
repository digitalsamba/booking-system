# Email Integration

This document provides information about the email integration in the Booking System.

## Overview

The Booking System supports sending email notifications for various events such as:

- Booking confirmations to customers
- Booking notifications to providers
- Booking reminders before appointments
- Booking cancellation notices

The system supports multiple email service providers:

1. **SMTP** - Standard SMTP servers
2. **SendGrid** - SendGrid email service
3. **Amazon SES** - Amazon Simple Email Service

## Configuration

### Environment Variables

Email configuration is stored in the `.env` file. The following variables are available:

```
# Email Configuration
EMAIL_PROVIDER=smtp        # smtp, sendgrid, ses
EMAIL_FROM=bookings@example.com
EMAIL_FROM_NAME=Booking System

# SMTP Configuration
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=username
SMTP_PASSWORD=password
SMTP_ENCRYPTION=tls       # tls, ssl, null

# SendGrid Configuration
SENDGRID_API_KEY=your_sendgrid_api_key

# Amazon SES Configuration
SES_KEY=your_aws_key
SES_SECRET=your_aws_secret
SES_REGION=us-east-1
```

### Provider-Specific Configuration

#### SMTP

For SMTP, you need to configure:

- `SMTP_HOST`: Your SMTP server hostname
- `SMTP_PORT`: SMTP port (usually 587 for TLS, 465 for SSL)
- `SMTP_USERNAME`: SMTP username for authentication
- `SMTP_PASSWORD`: SMTP password for authentication
- `SMTP_ENCRYPTION`: Encryption type (tls, ssl, or empty for no encryption)

#### SendGrid

For SendGrid, you need:

- `SENDGRID_API_KEY`: Your SendGrid API key

#### Amazon SES

For Amazon SES, you need:

- `SES_KEY`: Your AWS access key ID
- `SES_SECRET`: Your AWS secret access key
- `SES_REGION`: AWS region for SES (e.g., us-east-1)

## API Endpoints

The system provides several API endpoints for managing email configuration:

### Get Email Configuration

```
GET /email/config
```

Returns the current email configuration for the authenticated provider.

### Save Email Configuration

```
POST /email/config
```

Saves a custom email configuration for the authenticated provider.

Example request body:
```json
{
  "provider_type": "smtp",
  "settings": {
    "host": "smtp.example.com",
    "port": 587,
    "username": "user@example.com",
    "password": "password123",
    "encryption": "tls",
    "from_email": "bookings@example.com",
    "from_name": "My Booking Service"
  }
}
```

### Reset Email Configuration

```
DELETE /email/config
```

Resets the provider's email configuration to use the system default.

### Get Supported Providers

```
GET /email/providers
```

Returns a list of supported email providers.

### Send Test Email

```
POST /email/test
```

Sends a test email to verify the configuration.

Example request body:
```json
{
  "email": "recipient@example.com"
}
```

## Email Templates

Email templates are located in the `templates/emails/` directory:

- Plain text templates: `[template_name].php`
- HTML templates: `[template_name]_html.php`

Available templates:
- `booking_confirmation`: Sent to customers when a booking is created
- `booking_notification`: Sent to providers when a booking is created
- `booking_reminder`: Sent to customers before a scheduled booking
- `booking_cancellation`: Sent to customers when a booking is cancelled

## Adding Custom Email Providers

To add a new email provider:

1. Create a new provider class in `src/Utils/Email/Providers/` that extends `BaseEmailProvider`
2. Implement the required methods
3. Add the new provider to `EmailServiceFactory`

## Dependencies

The email system uses:

- PHPMailer for SMTP email sending
- Native cURL functions for API-based providers like SendGrid and Amazon SES

## Troubleshooting

### Common Issues

1. **Emails not sending**:
   - Check your email provider configuration
   - Verify SMTP credentials or API keys
   - Check server logs for error messages

2. **Templates not loading**:
   - Ensure template files exist in the correct location
   - Check file permissions

3. **Slow email sending**:
   - Consider using an API-based provider like SendGrid for better performance
   - For SMTP, check your server's connection to the SMTP server

4. **Email going to spam**:
   - Set up proper SPF, DKIM, and DMARC records for your domain
   - Use a reputable email service provider
   - Avoid spam trigger words in subject lines and content 