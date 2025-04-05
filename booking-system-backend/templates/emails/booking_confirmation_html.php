<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #3f51b5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .booking-details {
            background-color: #f5f5f5;
            border-left: 4px solid #3f51b5;
            padding: 15px;
            margin: 20px 0;
        }
        .meeting-link {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        .notes {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Booking Confirmation</h1>
    </div>
    
    <div class="content">
        <p>Dear <strong><?php echo htmlspecialchars($customer_name); ?></strong>,</p>
        
        <p>Your booking with <strong><?php echo htmlspecialchars($provider_name); ?></strong> has been confirmed.</p>
        
        <div class="booking-details">
            <h2>Booking Details</h2>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($booking_date); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars($start_time); ?> - <?php echo htmlspecialchars($end_time); ?></p>
            <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_id); ?></p>
        </div>
        
        <?php if (!empty($customer_link)): ?>
        <div class="meeting-link">
            <h2>Virtual Meeting</h2>
            <p><a href="<?php echo htmlspecialchars($customer_link); ?>" target="_blank">Click here to join the meeting</a></p>
            <p><small>(This link will be active at the time of your appointment)</small></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($notes)): ?>
        <div class="notes">
            <h2>Additional Notes</h2>
            <p><?php echo nl2br(htmlspecialchars($notes)); ?></p>
        </div>
        <?php endif; ?>
        
        <p>Thank you for using our booking system.</p>
        
        <p>
            Regards,<br>
            <?php echo htmlspecialchars($company_name); ?>
        </p>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company_name); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 