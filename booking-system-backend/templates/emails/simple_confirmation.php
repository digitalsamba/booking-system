<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        h1 {
            color: #4a6cf7;
            margin-bottom: 20px;
        }
        .booking-details {
            background-color: #f9f9f9;
            border-left: 4px solid #4a6cf7;
            padding: 15px;
            margin-bottom: 20px;
        }
        .booking-details h2 {
            margin-top: 0;
            color: #4a6cf7;
        }
        .booking-item {
            margin-bottom: 10px;
        }
        .booking-label {
            font-weight: bold;
        }
        .button {
            display: inline-block;
            background-color: #4a6cf7;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Booking Confirmation</h1>
    </div>

    <p>Dear <?php echo $customerName; ?>,</p>
    
    <p>Your booking with <?php echo $providerName; ?> has been confirmed.</p>
    
    <div class="booking-details">
        <h2>Booking Details</h2>
        <div class="booking-item">
            <span class="booking-label">Date:</span> <?php echo $emailData['booking_date']; ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Time:</span> <?php echo $emailData['start_time']; ?> - <?php echo $emailData['end_time']; ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Booking ID:</span> <?php echo $bookingId; ?>
        </div>
        
        <?php if (!empty($emailData['customer_link'])): ?>
        <div class="booking-item">
            <span class="booking-label">Join Meeting:</span><br>
            <a href="<?php echo $emailData['customer_link']; ?>" class="button">Join Video Meeting</a>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($emailData['notes'])): ?>
        <div class="booking-item">
            <span class="booking-label">Additional Notes:</span><br>
            <?php echo nl2br($emailData['notes']); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <p>Thank you for using our booking system.</p>
    
    <p>Regards,<br>
    <?php echo $emailData['company_name']; ?></p>
    
    <div class="footer">
        <p>This is an automated email, please do not reply.</p>
    </div>
</body>
</html>
