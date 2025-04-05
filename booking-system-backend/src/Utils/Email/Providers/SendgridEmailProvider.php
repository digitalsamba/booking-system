<?php

namespace App\Utils\Email\Providers;

use App\Utils\Email\EmailService;
use App\Utils\Email\EmailConfig;

/**
 * SendGrid email provider
 */
class SendgridEmailProvider implements EmailService {
    /** @var string SendGrid API key */
    private $apiKey;
    
    /** @var bool Whether to disable SSL verification */
    private $disableSSLVerify;
    
    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = []) {
        // Get API key from config or environment
        $this->apiKey = $config['api_key'] ?? EmailConfig::get('SENDGRID_API_KEY') ?? '';
        
        if (empty($this->apiKey)) {
            error_log("SendgridEmailProvider: No API key provided");
        } else {
            error_log("SendgridEmailProvider: API key found (length: " . strlen($this->apiKey) . ")");
        }
        
        // Check if we should disable SSL verification (for development only)
        $appEnv = EmailConfig::get('APP_ENV', 'production');
        $this->disableSSLVerify = $appEnv === 'development' || $config['disable_ssl_verify'] ?? false;
        
        if ($this->disableSSLVerify) {
            error_log("SendgridEmailProvider: WARNING - SSL verification disabled for development");
        }
    }
    
    /**
     * @inheritDoc
     */
    public function send(string $to, string $subject, string $body, ?string $htmlBody = null, array $attachments = [], array $options = []): bool {
        // Build the payload
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $options['from_email'] ?? EmailConfig::get('EMAIL_FROM') ?? 'noreply@example.com',
                'name' => $options['from_name'] ?? EmailConfig::get('EMAIL_FROM_NAME') ?? 'Booking System'
            ],
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $body
                ]
            ]
        ];
        
        // Add HTML content if provided
        if (!empty($htmlBody)) {
            $data['content'][] = [
                'type' => 'text/html',
                'value' => $htmlBody
            ];
        }
        
        // Add CC recipients if specified
        if (isset($options['cc']) && is_array($options['cc'])) {
            $data['personalizations'][0]['cc'] = [];
            foreach ($options['cc'] as $cc) {
                $data['personalizations'][0]['cc'][] = ['email' => $cc];
            }
        }
        
        // Add BCC recipients if specified
        if (isset($options['bcc']) && is_array($options['bcc'])) {
            $data['personalizations'][0]['bcc'] = [];
            foreach ($options['bcc'] as $bcc) {
                $data['personalizations'][0]['bcc'][] = ['email' => $bcc];
            }
        }
        
        // Add reply-to if specified
        if (isset($options['reply_to'])) {
            $data['reply_to'] = ['email' => $options['reply_to']];
        }
        
        // Add attachments if provided
        if (!empty($attachments)) {
            $data['attachments'] = [];
            
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    // Simple filename
                    $filename = basename($attachment);
                    $content = base64_encode(file_get_contents($attachment));
                    $type = mime_content_type($attachment) ?: 'application/octet-stream';
                    
                    $data['attachments'][] = [
                        'content' => $content,
                        'type' => $type,
                        'filename' => $filename,
                        'disposition' => 'attachment'
                    ];
                } else if (is_array($attachment) && isset($attachment['content']) && isset($attachment['filename'])) {
                    // Prepared attachment
                    $data['attachments'][] = [
                        'content' => $attachment['content'],
                        'type' => $attachment['type'] ?? 'application/octet-stream',
                        'filename' => $attachment['filename'],
                        'disposition' => $attachment['disposition'] ?? 'attachment'
                    ];
                }
            }
        }
        
        // Send the request
        return $this->sendApiRequest($data);
    }
    
    /**
     * @inheritDoc
     */
    public function sendTemplate(string $to, string $subject, string $templateName, array $templateVars = [], array $attachments = [], array $options = []): bool {
        // For SendGrid, template name should be a template ID
        $templateId = $templateName;
        
        // Build the payload
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ],
                    'dynamic_template_data' => $templateVars
                ]
            ],
            'from' => [
                'email' => $options['from_email'] ?? EmailConfig::get('EMAIL_FROM') ?? 'noreply@example.com',
                'name' => $options['from_name'] ?? EmailConfig::get('EMAIL_FROM_NAME') ?? 'Booking System'
            ],
            'template_id' => $templateId
        ];
        
        // Add subject if provided (not used for dynamic templates)
        if (!empty($subject)) {
            $data['personalizations'][0]['subject'] = $subject;
        }
        
        // Add CC recipients if specified
        if (isset($options['cc']) && is_array($options['cc'])) {
            $data['personalizations'][0]['cc'] = [];
            foreach ($options['cc'] as $cc) {
                $data['personalizations'][0]['cc'][] = ['email' => $cc];
            }
        }
        
        // Add BCC recipients if specified
        if (isset($options['bcc']) && is_array($options['bcc'])) {
            $data['personalizations'][0]['bcc'] = [];
            foreach ($options['bcc'] as $bcc) {
                $data['personalizations'][0]['bcc'][] = ['email' => $bcc];
            }
        }
        
        // Add reply-to if specified
        if (isset($options['reply_to'])) {
            $data['reply_to'] = ['email' => $options['reply_to']];
        }
        
        // Add attachments if provided
        if (!empty($attachments)) {
            $data['attachments'] = [];
            
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    // Simple filename
                    $filename = basename($attachment);
                    $content = base64_encode(file_get_contents($attachment));
                    $type = mime_content_type($attachment) ?: 'application/octet-stream';
                    
                    $data['attachments'][] = [
                        'content' => $content,
                        'type' => $type,
                        'filename' => $filename,
                        'disposition' => 'attachment'
                    ];
                } else if (is_array($attachment) && isset($attachment['content']) && isset($attachment['filename'])) {
                    // Prepared attachment
                    $data['attachments'][] = [
                        'content' => $attachment['content'],
                        'type' => $attachment['type'] ?? 'application/octet-stream',
                        'filename' => $attachment['filename'],
                        'disposition' => $attachment['disposition'] ?? 'attachment'
                    ];
                }
            }
        }
        
        // Send the request
        return $this->sendApiRequest($data);
    }
    
    /**
     * @inheritDoc
     */
    public function sendEmail(string $to, string $subject, string $textBody, ?string $htmlBody = null, ?string $from = null, ?string $fromName = null, ?string $replyTo = null, array $attachments = []): bool {
        // Create options array
        $options = [];
        if ($from) $options['from_email'] = $from;
        if ($fromName) $options['from_name'] = $fromName;
        if ($replyTo) $options['reply_to'] = $replyTo;
        
        // Call the original send method
        return $this->send($to, $subject, $textBody, $htmlBody, $attachments, $options);
    }
    
    /**
     * @inheritDoc
     */
    public function sendTemplateEmail(string $to, string $templateId, array $templateData = [], ?string $from = null, ?string $fromName = null, ?string $replyTo = null, array $attachments = []): bool {
        // Create options array
        $options = [];
        if ($from) $options['from_email'] = $from;
        if ($fromName) $options['from_name'] = $fromName;
        if ($replyTo) $options['reply_to'] = $replyTo;
        
        // Call the original send method
        return $this->sendTemplate($to, '', $templateId, $templateData, $attachments, $options);
    }
    
    /**
     * Send request to SendGrid API
     *
     * @param array $data Request payload
     * @return bool Success status
     */
    private function sendApiRequest(array $data): bool {
        try {
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // Disable SSL verification for development if needed
            if ($this->disableSSLVerify) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            
            // Execute request
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            $httpCode = $info['http_code'];
            
            if (curl_errno($ch)) {
                error_log("SendgridEmailProvider: cURL error: " . curl_error($ch));
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            // Check for success (2xx status code)
            $success = $httpCode >= 200 && $httpCode < 300;
            
            if (!$success) {
                error_log("SendgridEmailProvider: API error (HTTP $httpCode): " . $response);
            } else {
                error_log("SendgridEmailProvider: Email sent successfully (HTTP $httpCode)");
            }
            
            return $success;
        } catch (\Exception $e) {
            error_log("SendgridEmailProvider: Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the email service provider name
     *
     * @return string Provider name
     */
    public function getProviderName(): string {
        return 'sendgrid';
    }
    
    /**
     * Check if the email service is properly configured
     *
     * @return bool Configuration status
     */
    public function isConfigured(): bool {
        return !empty($this->apiKey);
    }
} 