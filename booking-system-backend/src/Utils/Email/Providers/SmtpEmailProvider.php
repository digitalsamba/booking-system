<?php

namespace App\Utils\Email\Providers;

use App\Utils\Email\BaseEmailProvider;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * SMTP Email Provider
 * 
 * Implements email sending via SMTP server using PHPMailer
 */
class SmtpEmailProvider extends BaseEmailProvider {
    /**
     * Send a simple email (implementing EmailService interface)
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $textBody Email body (plain text)
     * @param string|null $htmlBody Optional HTML body
     * @param string|null $from Optional sender email
     * @param string|null $fromName Optional sender name
     * @param string|null $replyTo Optional reply-to email
     * @param array $attachments Optional array of attachments
     * @return bool Success status
     */
    public function sendEmail(string $to, string $subject, string $textBody, ?string $htmlBody = null, ?string $from = null, ?string $fromName = null, ?string $replyTo = null, array $attachments = []): bool {
        return $this->send($to, $subject, $textBody, $htmlBody, $attachments, [
            'from' => $from,
            'fromName' => $fromName,
            'replyTo' => $replyTo
        ]);
    }
    
    /**
     * Send email using a template (implementing EmailService interface)
     *
     * @param string $to Recipient email address
     * @param string $templateId ID or name of the template to use
     * @param array $templateData Variables to pass to the template
     * @param string|null $from Optional sender email
     * @param string|null $fromName Optional sender name
     * @param string|null $replyTo Optional reply-to email
     * @param array $attachments Optional array of attachments
     * @return bool Success status
     */
    public function sendTemplateEmail(string $to, string $templateId, array $templateData = [], ?string $from = null, ?string $fromName = null, ?string $replyTo = null, array $attachments = []): bool {
        // Render the template
        $htmlBody = $this->renderTemplate($templateId, $templateData);
        $textBody = strip_tags($htmlBody); // Simple text version
        
        return $this->sendEmail($to, $templateData['subject'] ?? 'Notification', $textBody, $htmlBody, $from, $fromName, $replyTo, $attachments);
    }
    
    /**
     * Internal send method
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (plain text)
     * @param string $htmlBody Optional HTML body
     * @param array $attachments Optional array of attachments
     * @param array $options Additional options
     * @return bool Success status
     */
    protected function send(string $to, string $subject, string $body, string $htmlBody = null, array $attachments = [], array $options = []): bool {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->getHost();
            $mail->SMTPAuth = true;
            $mail->Username = $this->getUsername();
            $mail->Password = $this->getPassword();
            $mail->Port = $this->getPort();
            
            // Set encryption if needed
            $encryption = $this->getEncryption();
            if ($encryption) {
                $mail->SMTPSecure = $encryption;
            }
            
            // Recipients
            $mail->setFrom($this->getDefaultFromEmail(), $this->getDefaultFromName());
            $mail->addAddress($to);
            
            // Add CC recipients if specified
            if (isset($options['cc']) && is_array($options['cc'])) {
                foreach ($options['cc'] as $cc) {
                    $mail->addCC($cc);
                }
            }
            
            // Add BCC recipients if specified
            if (isset($options['bcc']) && is_array($options['bcc'])) {
                foreach ($options['bcc'] as $bcc) {
                    $mail->addBCC($bcc);
                }
            }
            
            // Add reply-to if specified
            if (isset($options['reply_to'])) {
                $mail->addReplyTo($options['reply_to']);
            }
            
            // Content
            $mail->Subject = $subject;
            $mail->Body = $htmlBody ?? $body;
            
            // Set alt body if HTML is provided
            if ($htmlBody) {
                $mail->isHTML(true);
                $mail->AltBody = $body;
            }
            
            // Add attachments
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    // Simple filename
                    $mail->addAttachment($attachment);
                } else if (is_array($attachment) && isset($attachment['path'])) {
                    // Advanced attachment with options
                    $filename = $attachment['name'] ?? basename($attachment['path']);
                    $mail->addAttachment(
                        $attachment['path'],
                        $filename,
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? ''
                    );
                }
            }
            
            // Send the email
            return $mail->send();
            
        } catch (Exception $e) {
            // Log the error
            error_log("Email sending failed: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Get SMTP host from config or environment
     *
     * @return string
     */
    protected function getHost(): string {
        return $this->config['host'] ?? getenv('SMTP_HOST') ?? 'localhost';
    }
    
    /**
     * Get SMTP port from config or environment
     *
     * @return int
     */
    protected function getPort(): int {
        return (int)($this->config['port'] ?? getenv('SMTP_PORT') ?? 25);
    }
    
    /**
     * Get SMTP username from config or environment
     *
     * @return string
     */
    protected function getUsername(): string {
        return $this->config['username'] ?? getenv('SMTP_USERNAME') ?? '';
    }
    
    /**
     * Get SMTP password from config or environment
     *
     * @return string
     */
    protected function getPassword(): string {
        return $this->config['password'] ?? getenv('SMTP_PASSWORD') ?? '';
    }
    
    /**
     * Get SMTP encryption type from config or environment
     *
     * @return string
     */
    protected function getEncryption(): string {
        $encryption = $this->config['encryption'] ?? getenv('SMTP_ENCRYPTION') ?? '';
        
        // Convert empty string, "none", or null to empty string (no encryption)
        if (empty($encryption) || strtolower($encryption) === 'none') {
            return '';
        }
        
        return $encryption;
    }
    
    /**
     * Get the email service provider name
     *
     * @return string Provider name
     */
    public function getProviderName(): string {
        return 'smtp';
    }
    
    /**
     * Check if the email service is properly configured
     *
     * @return bool Configuration status
     */
    public function isConfigured(): bool {
        // Basic configuration check - at minimum we need host and port
        return !empty($this->getHost()) && !empty($this->getPort());
    }
} 