@echo off
echo Installing email-related dependencies...

:: Check if Composer is installed
where composer >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Error: Composer is required but not installed.
    echo Please install Composer first: https://getcomposer.org/download/
    exit /b 1
)

:: Go to the backend directory
cd /d "%~dp0"

:: Install PHPMailer for SMTP support
echo Installing PHPMailer...
composer require phpmailer/phpmailer

echo Creating email template directories...
if not exist "templates\emails" mkdir "templates\emails"

:: Check if .env file exists, if not create from .env.example
if not exist .env (
    echo Creating .env file from .env.example...
    if exist .env.example (
        copy .env.example .env
    ) else (
        echo # Environment Configuration> .env
        echo.>> .env
        echo # JWT Configuration>> .env
        echo JWT_SECRET=your-very-secure-jwt-secret-key>> .env
        echo JWT_EXPIRY=86400>> .env
        echo.>> .env
        echo # Database Configuration>> .env
        echo DB_HOST=localhost>> .env
        echo DB_PORT=27017>> .env
        echo DB_NAME=booking_system>> .env
        echo DB_USER=>> .env
        echo DB_PASS=>> .env
        echo.>> .env
        echo # Other Configuration>> .env
        echo DEBUG=true>> .env
        echo APP_ENV=development>> .env
        echo.>> .env
        echo # Email Configuration>> .env
        echo EMAIL_PROVIDER=smtp>> .env
        echo EMAIL_FROM=bookings@example.com>> .env
        echo EMAIL_FROM_NAME=Booking System>> .env
        echo.>> .env
        echo # SMTP Configuration>> .env
        echo SMTP_HOST=smtp.example.com>> .env
        echo SMTP_PORT=587>> .env
        echo SMTP_USERNAME=username>> .env
        echo SMTP_PASSWORD=password>> .env
        echo SMTP_ENCRYPTION=tls>> .env
        echo.>> .env
        echo # SendGrid Configuration>> .env
        echo SENDGRID_API_KEY=your_sendgrid_api_key>> .env
        echo.>> .env
        echo # Amazon SES Configuration>> .env
        echo SES_KEY=your_aws_key>> .env
        echo SES_SECRET=your_aws_secret>> .env
        echo SES_REGION=us-east-1>> .env
    )
    echo Created .env file. Please update it with your email configuration.
)

echo Creating basic email templates...

:: Create booking confirmation template if it doesn't exist
if not exist "templates\emails\booking_confirmation.php" (
    echo ^<?php
    echo Dear ^<?php echo $customer_name; ^?^>,> "templates\emails\booking_confirmation.php"
    echo.>> "templates\emails\booking_confirmation.php"
    echo Your booking with ^<?php echo $provider_name; ^?^> has been confirmed.>> "templates\emails\booking_confirmation.php"
    echo.>> "templates\emails\booking_confirmation.php"
    echo Booking Details:>> "templates\emails\booking_confirmation.php"
    echo Date: ^<?php echo $booking_date; ^?^>>> "templates\emails\booking_confirmation.php"
    echo Time: ^<?php echo $start_time; ^?^> - ^<?php echo $end_time; ^?^>>> "templates\emails\booking_confirmation.php"
    echo Booking ID: ^<?php echo $booking_id; ^?^>>> "templates\emails\booking_confirmation.php"
    echo.>> "templates\emails\booking_confirmation.php"
    echo ^<?php if (!empty($customer_link)): ^?^>>> "templates\emails\booking_confirmation.php"
    echo Join Meeting Link: ^<?php echo $customer_link; ^?^>>> "templates\emails\booking_confirmation.php"
    echo (This link will be active at the time of your appointment)>> "templates\emails\booking_confirmation.php"
    echo ^<?php endif; ^?^>>> "templates\emails\booking_confirmation.php"
    echo.>> "templates\emails\booking_confirmation.php"
    echo ^<?php if (!empty($notes)): ^?^>>> "templates\emails\booking_confirmation.php"
    echo Additional Notes:>> "templates\emails\booking_confirmation.php"
    echo ^<?php echo $notes; ^?^>>> "templates\emails\booking_confirmation.php"
    echo ^<?php endif; ^?^>>> "templates\emails\booking_confirmation.php"
    echo.>> "templates\emails\booking_confirmation.php"
    echo Thank you for using our booking system.>> "templates\emails\booking_confirmation.php"
    echo.>> "templates\emails\booking_confirmation.php"
    echo Regards,>> "templates\emails\booking_confirmation.php"
    echo ^<?php echo $company_name; ^?^>>> "templates\emails\booking_confirmation.php"
)

echo Installation complete!
echo Please update your .env file with your email configuration.
echo See docs/email-integration.md for more information.
pause 