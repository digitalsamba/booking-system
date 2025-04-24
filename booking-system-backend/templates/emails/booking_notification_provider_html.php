<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Notification</title>
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
            background-color: #f0f4ff; /* Lighter blue */
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #a0b3f0;
        }
        .join-button {
            display: inline-block;
            background-color: #28a745; /* Green */
            color: white !important;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            margin-top: 15px;
            margin-bottom: 15px;
            font-weight: bold;
            text-align: center;
            width: 200px;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .join-button:hover {
            background-color: #218838;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
        <h1>New Booking Notification</h1>
    </div>

    <p>Hello <?php echo htmlspecialchars($provider_name); ?>,</p>
    
    <p>You have received a new booking from <strong><?php echo htmlspecialchars($customer_name); ?></strong>.</p>
    
    <div class="booking-details">
        <h2>Booking Details</h2>
        <div class="booking-item">
            <span class="booking-label">Customer Name:</span> <?php echo htmlspecialchars($customer_name); ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Customer Email:</span> <?php echo htmlspecialchars($customer_email); ?>
        </div>
        <hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">
        <div class="booking-item">
            <span class="booking-label">Date:</span> <?php echo htmlspecialchars($booking_date); ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Time:</span> <?php echo htmlspecialchars($start_time); ?> - <?php echo htmlspecialchars($end_time); ?>
        </div>
        <div class="booking-item">
            <span class="booking-label">Booking ID:</span> <?php echo htmlspecialchars($booking_id); ?>
        </div>
        
        <?php if (!empty($provider_link)): ?>
        <div class="booking-item">
            <span class="booking-label">Your Meeting Link:</span><br>
            <a href="<?php echo htmlspecialchars($provider_link); ?>" class="join-button">Start Your Meeting</a>
             <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                Use this link to start and manage your meeting at the scheduled time.
            </p>
       </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($notes)): ?>
    <div class="notes">
        <h3>Customer Notes</h3>
        <p><?php echo nl2br(htmlspecialchars($notes)); ?></p>
    </div>
    <?php endif; ?>
    
    <p>You can view and manage this booking in your provider dashboard.</p>
    
    <p>Regards,<br><?php echo htmlspecialchars($company_name); ?></p>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company_name); ?>. All rights reserved.</p>
    </div>
</body>
</html> 