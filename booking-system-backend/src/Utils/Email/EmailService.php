<?php

namespace App\Utils\Email;

/**
 * Interface for email services
 * 
 * This interface defines methods that must be implemented by all email service providers.
 */
interface EmailService {
    /**
     * Send a simple email
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
    public function sendEmail(string $to, string $subject, string $textBody, ?string $htmlBody = null, ?string $from = null, ?string $fromName = null, ?string $replyTo = null, array $attachments = []): bool;
    
    /**
     * Send email using a template
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
    public function sendTemplateEmail(string $to, string $templateId, array $templateData = [], ?string $from = null, ?string $fromName = null, ?string $replyTo = null, array $attachments = []): bool;
} 