
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
                <p>Hello <strong><?php echo htmlspecialchars($name); ?></strong>,</p>
                <p>This is a test email sent using a template.</p>
                <p>Regards,<br>Booking System</p>
            </div>
        </body>
        </html>
        