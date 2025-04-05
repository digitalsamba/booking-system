<?php

namespace App\Utils\Email;

/**
 * Base abstract class for all email providers
 */
abstract class BaseEmailProvider implements EmailService {
    /**
     * Configuration settings
     *
     * @var array
     */
    protected $config = [];
    
    /**
     * Constructor
     *
     * @param array $config Optional configuration settings
     */
    public function __construct(array $config = []) {
        $this->config = $config;
    }
    
    /**
     * Get default sender email address
     *
     * @return string
     */
    protected function getDefaultFromEmail(): string {
        return $this->config['from_email'] ?? getenv('EMAIL_FROM') ?? 'bookings@example.com';
    }
    
    /**
     * Get default sender name
     *
     * @return string
     */
    protected function getDefaultFromName(): string {
        return $this->config['from_name'] ?? getenv('EMAIL_FROM_NAME') ?? 'Booking System';
    }
    
    /**
     * Render a template with variables
     *
     * @param string $templateName Name of the template to use
     * @param array $templateVars Variables to pass to the template
     * @return string Rendered template
     */
    protected function renderTemplate(string $templateName, array $templateVars = []): string {
        // Template path
        $templatePath = __DIR__ . '/../../../templates/emails/' . $templateName . '.php';
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Email template not found: {$templateName}");
        }
        
        // Extract variables to make them available in the template
        extract($templateVars);
        
        // Capture output
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
    
    /**
     * Send an email using a template
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $templateName Name of the template to use
     * @param array $templateVars Variables to pass to the template
     * @param array $attachments Optional array of attachments
     * @param array $options Additional provider-specific options
     * @return bool Success status
     */
    public function sendTemplate(string $to, string $subject, string $templateName, array $templateVars = [], array $attachments = [], array $options = []): bool {
        // Render text version
        $body = $this->renderTemplate($templateName, $templateVars);
        
        // Render HTML version if it exists
        $htmlBody = null;
        $htmlTemplatePath = __DIR__ . '/../../../templates/emails/' . $templateName . '_html.php';
        if (file_exists($htmlTemplatePath)) {
            // Capture output
            extract($templateVars);
            ob_start();
            include $htmlTemplatePath;
            $htmlBody = ob_get_clean();
        }
        
        // Send the email
        return $this->send($to, $subject, $body, $htmlBody, $attachments, $options);
    }
} 