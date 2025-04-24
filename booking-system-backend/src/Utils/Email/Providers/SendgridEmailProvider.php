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
    public function __construct(array $config = [])
    {
        // First try to use a provided API key from config
        if (isset($config['api_key']) && !empty($config['api_key'])) {
            $this->apiKey = $config['api_key'];
        }
   
        // Log debug information
        error_log("SendgridEmailProvider: Initialized with API key (length: " . strlen($this->apiKey) . ")");
        
        // Check if we should disable SSL verification (for development only)
        $appEnv = EmailConfig::get('APP_ENV', 'production');
        $this->disableSSLVerify = $appEnv === 'development' || $config['disable_ssl_verify'] ?? false;
        
        if ($this->disableSSLVerify) {
            error_log("EMAIL DEBUG: WARNING - SSL verification disabled for development");
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
            // Log important parts of the request data - Keep this one
            $to = $data['personalizations'][0]['to'][0]['email'] ?? 'unknown';
            $subject = $data['personalizations'][0]['subject'] ?? 'No subject';
            error_log("SendgridEmailProvider: Sending to: {$to}, Subject: {$subject}");
            
            // Initialize cURL
            $ch = curl_init();
            if (!$ch) {
                error_log("SendgridEmailProvider: CRITICAL ERROR - curl_init() failed!");
                return false;
            }
            
            // Set options
            $curlOptions = [
                CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => !$this->disableSSLVerify,
                CURLOPT_SSL_VERIFYHOST => $this->disableSSLVerify ? 0 : 2
            ];
            $setoptResult = curl_setopt_array($ch, $curlOptions);
            if (!$setoptResult) {
                error_log("SendgridEmailProvider: CRITICAL ERROR - curl_setopt_array() failed!");
                curl_close($ch);
                return false;
            }
            
            // Execute request
            $response = curl_exec($ch);
            
            // Check for cURL errors immediately after execution
            if (curl_errno($ch)) {
                $curlError = curl_error($ch);
                error_log("SendgridEmailProvider: CRITICAL cURL Error after exec: {$curlError}");
                curl_close($ch);
                return false;
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Close cURL handle
            curl_close($ch);
            
            // Log the result
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("SendgridEmailProvider: Email sent successfully (HTTP {$httpCode})");
                return true;
            } else {
                error_log("SendgridEmailProvider: FAILED to send email (HTTP {$httpCode})");
                if ($response) {
                    error_log("SendgridEmailProvider: API response: {$response}");
                }
                $jsonResponse = json_decode($response, true);
                if ($jsonResponse && isset($jsonResponse['errors'])) {
                    foreach ($jsonResponse['errors'] as $err) {
                        error_log("SendgridEmailProvider: SendGrid error: " . json_encode($err));
                    }
                }
                return false;
            }
            
        } catch (\Throwable $e) { 
            error_log("SendgridEmailProvider: FATAL Exception/Error: " . $e->getMessage());
            error_log("SendgridEmailProvider: Trace: " . $e->getTraceAsString());
            if (isset($ch) && is_resource($ch)) {
                curl_close($ch);
            }
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
    
    /**
     * Get the API key (for testing purposes only)
     * 
     * @return string The API key
     */
    public function getApiKey(): string {
        return $this->apiKey ?? '';
    }
} 