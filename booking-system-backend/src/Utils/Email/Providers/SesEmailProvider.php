<?php

namespace App\Utils\Email\Providers;

use App\Utils\Email\BaseEmailProvider;

/**
 * Amazon SES Email Provider
 * 
 * Implements email sending via Amazon SES API
 */
class SesEmailProvider extends BaseEmailProvider {
    /**
     * Send a simple email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (plain text)
     * @param string $htmlBody Optional HTML body
     * @param array $attachments Optional array of attachments
     * @param array $options Additional options
     * @return bool Success status
     */
    public function send(string $to, string $subject, string $body, string $htmlBody = null, array $attachments = [], array $options = []): bool {
        // Get AWS credentials
        $key = $this->getKey();
        $secret = $this->getSecret();
        $region = $this->getRegion();
        
        if (empty($key) || empty($secret)) {
            error_log("Amazon SES credentials not configured");
            return false;
        }
        
        // Create a basic AWS signature
        $date = gmdate('Ymd');
        $timestamp = gmdate('Ymd\THis\Z');
        $service = 'ses';
        $host = "email.{$region}.amazonaws.com";
        $endpoint = "https://{$host}/";
        
        // Prepare the request parameters
        $params = [
            'Action' => 'SendEmail',
            'Destination.ToAddresses.member.1' => $to,
            'Source' => $this->getDefaultFromEmail(),
            'Message.Subject.Data' => $subject,
            'Message.Body.Text.Data' => $body
        ];
        
        // Add HTML body if provided
        if (!empty($htmlBody)) {
            $params['Message.Body.Html.Data'] = $htmlBody;
        }
        
        // Add CC recipients if specified
        if (isset($options['cc']) && is_array($options['cc'])) {
            $ccCount = 1;
            foreach ($options['cc'] as $cc) {
                $params["Destination.CcAddresses.member.{$ccCount}"] = $cc;
                $ccCount++;
            }
        }
        
        // Add BCC recipients if specified
        if (isset($options['bcc']) && is_array($options['bcc'])) {
            $bccCount = 1;
            foreach ($options['bcc'] as $bcc) {
                $params["Destination.BccAddresses.member.{$bccCount}"] = $bcc;
                $bccCount++;
            }
        }
        
        // Add reply-to if specified
        if (isset($options['reply_to'])) {
            $params['ReplyToAddresses.member.1'] = $options['reply_to'];
        }
        
        // Handle attachments (Amazon SES requires MIME formatting for attachments)
        // This is complex and would require a MIME library to implement properly
        // For now, we'll log a warning if attachments are provided but not supported
        if (!empty($attachments)) {
            error_log("Warning: Amazon SES provider doesn't support attachments directly. Use a MIME library for attachments.");
        }
        
        // Add timestamp and other required parameters
        $params['Version'] = '2010-12-01';
        $params['AWSAccessKeyId'] = $key;
        $params['Timestamp'] = $timestamp;
        
        // Sort parameters by name
        ksort($params);
        
        // Create the canonical query string
        $canonicalQuery = '';
        foreach ($params as $key => $value) {
            $canonicalQuery .= '&' . $this->aws_urlencode($key) . '=' . $this->aws_urlencode($value);
        }
        $canonicalQuery = substr($canonicalQuery, 1); // Remove the first '&'
        
        // Create the string to sign
        $stringToSign = "GET\n{$host}\n/\n{$canonicalQuery}";
        
        // Calculate the signature
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));
        
        // Add the signature to the query string
        $canonicalQuery .= '&Signature=' . $this->aws_urlencode($signature);
        
        // Make the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint . '?' . $canonicalQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for success
        $success = $httpCode >= 200 && $httpCode < 300;
        
        if (!$success) {
            error_log("Amazon SES API error (HTTP $httpCode): " . $response);
        }
        
        return $success;
    }
    
    /**
     * URL encode for AWS signatures
     * 
     * @param string $string String to encode
     * @return string Encoded string
     */
    private function aws_urlencode($string) {
        return str_replace('%7E', '~', rawurlencode($string));
    }
    
    /**
     * Get AWS access key from config or environment
     *
     * @return string
     */
    protected function getKey(): string {
        return $this->config['key'] ?? getenv('SES_KEY') ?? '';
    }
    
    /**
     * Get AWS secret key from config or environment
     *
     * @return string
     */
    protected function getSecret(): string {
        return $this->config['secret'] ?? getenv('SES_SECRET') ?? '';
    }
    
    /**
     * Get AWS region from config or environment
     *
     * @return string
     */
    protected function getRegion(): string {
        return $this->config['region'] ?? getenv('SES_REGION') ?? 'us-east-1';
    }
    
    /**
     * Get the email service provider name
     *
     * @return string Provider name
     */
    public function getProviderName(): string {
        return 'ses';
    }
    
    /**
     * Check if the email service is properly configured
     *
     * @return bool Configuration status
     */
    public function isConfigured(): bool {
        return !empty($this->getKey()) && !empty($this->getSecret());
    }
} 