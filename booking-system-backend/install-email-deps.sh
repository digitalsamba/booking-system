#!/bin/bash

# Script to install email-related dependencies for the Booking System

echo "Installing email-related dependencies..."

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is required but not installed."
    echo "Please install Composer first: https://getcomposer.org/download/"
    exit 1
fi

# Go to the backend directory
cd "$(dirname "$0")"

# Install PHPMailer for SMTP support
echo "Installing PHPMailer..."
composer require phpmailer/phpmailer

echo "Creating email template directories..."
mkdir -p templates/emails

# Check if .env file exists, if not create from .env.example
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "# Environment Configuration
        
# JWT Configuration
JWT_SECRET=your-very-secure-jwt-secret-key
JWT_EXPIRY=86400

# Database Configuration
DB_HOST=localhost
DB_PORT=27017
DB_NAME=booking_system
DB_USER=
DB_PASS=

# Other Configuration
DEBUG=true
APP_ENV=development

# Email Configuration
EMAIL_PROVIDER=smtp
EMAIL_FROM=bookings@example.com
EMAIL_FROM_NAME=Booking System

# SMTP Configuration
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=username
SMTP_PASSWORD=password
SMTP_ENCRYPTION=tls

# SendGrid Configuration
SENDGRID_API_KEY=your_sendgrid_api_key

# Amazon SES Configuration
SES_KEY=your_aws_key
SES_SECRET=your_aws_secret
SES_REGION=us-east-1" > .env
    fi
    echo "Created .env file. Please update it with your email configuration."
fi

echo "Creating basic email templates..."

# Create booking confirmation template if it doesn't exist
if [ ! -f templates/emails/booking_confirmation.php ]; then
    echo 'Dear <?php echo $customer_name; ?>,

Your booking with <?php echo $provider_name; ?> has been confirmed.

Booking Details:
Date: <?php echo $booking_date; ?>
Time: <?php echo $start_time; ?> - <?php echo $end_time; ?>
Booking ID: <?php echo $booking_id; ?>

<?php if (!empty($customer_link)): ?>
Join Meeting Link: <?php echo $customer_link; ?>
(This link will be active at the time of your appointment)
<?php endif; ?>

<?php if (!empty($notes)): ?>
Additional Notes:
<?php echo $notes; ?>
<?php endif; ?>

Thank you for using our booking system.

Regards,
<?php echo $company_name; ?>' > templates/emails/booking_confirmation.php
fi

echo "Installation complete!"
echo "Please update your .env file with your email configuration."
echo "See docs/email-integration.md for more information." 