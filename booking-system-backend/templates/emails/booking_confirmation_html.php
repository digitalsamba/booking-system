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
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
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
        .notes {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #e0e0e0;
        }
        .join-button {
            display: inline-block;
            background-color: #4a6cf7;
            color: white !important;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            margin-top: 15px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Booking Confirmation</h1>
    </div>

    <p>Dear <?php echo htmlspecialchars($customer_name); ?>,</p>
    
    <p>Your booking with <?php echo htmlspecialchars($provider_name); ?> has been confirmed.</p>
    
    <div class="booking-details">
        <h2>Booking Details</h2>
        <div class="booking-item">
            <span class="booking-label">Date:</span> <?php echo htmlspecialchars($booking_date); ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Time:</span> <?php echo htmlspecialchars($start_time); ?> - <?php echo htmlspecialchars($end_time); ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Booking ID:</span> <?php echo htmlspecialchars($booking_id); ?>
        </div>
        
        <?php if (!empty($customer_link)): ?>
        <div class="booking-item">
            <span class="booking-label">Meeting Link:</span><br>
            <a href="<?php echo htmlspecialchars($customer_link); ?>" class="join-button">Join Meeting</a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($notes)): ?>
    <div class="notes">
        <h3>Additional Notes</h3>
        <p><?php echo nl2br(htmlspecialchars($notes)); ?></p>
    </div>
    <?php endif; ?>
    
    <p>Please make sure to join the meeting a few minutes before the scheduled time.</p>
    <p>If you need to cancel or reschedule, please contact us as soon as possible.</p>
    
    <p>Thank you for using our booking system.</p>
    
    <p>Regards,<br><?php echo htmlspecialchars($company_name); ?></p>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company_name); ?>. All rights reserved.</p>
    </div>
</body>
</html> 