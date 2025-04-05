<?php

namespace App\Utils\Email;

use App\Utils\Email\Providers\SmtpEmailProvider;
use App\Utils\Email\Providers\SendgridEmailProvider;
use App\Utils\Email\Providers\SesEmailProvider;

/**
 * Factory for creating email service providers
 */
class EmailServiceFactory {
    /**
     * Create an email service provider based on configuration
     *
     * @param string|null $provider Optional provider name to override default from .env
     * @param array $config Optional configuration settings
     * @return EmailService
     * @throws \Exception If provider is not supported
     */
    public static function create(?string $provider = null, array $config = []): EmailService {
        // If provider not specified, get from environment or config
        if ($provider === null) {
            $provider = EmailConfig::get('EMAIL_PROVIDER') ?: 'smtp';
        }
        
        // Create the appropriate provider
        switch (strtolower($provider)) {
            case 'smtp':
                return new SmtpEmailProvider($config);
            
            case 'sendgrid':
                // If config doesn't have api_key but we have it in ENV, add it
                if (!isset($config['api_key']) && EmailConfig::get('SENDGRID_API_KEY')) {
                    $config['api_key'] = EmailConfig::get('SENDGRID_API_KEY');
                }
                
                // Log if we found a SendGrid API key
                if (!isset($config['api_key'])) {
                    error_log("EmailServiceFactory: SendGrid API key NOT found");
                }
                
                return new SendgridEmailProvider($config);
            
            case 'ses':
                // If config doesn't have keys but we have them in ENV, add them
                if (!isset($config['key']) && EmailConfig::get('SES_KEY')) {
                    $config['key'] = EmailConfig::get('SES_KEY');
                }
                if (!isset($config['secret']) && EmailConfig::get('SES_SECRET')) {
                    $config['secret'] = EmailConfig::get('SES_SECRET');
                }
                if (!isset($config['region']) && EmailConfig::get('SES_REGION')) {
                    $config['region'] = EmailConfig::get('SES_REGION');
                }
                
                return new SesEmailProvider($config);
            
            default:
                throw new \Exception("Unsupported email provider: {$provider}");
        }
    }
    
    /**
     * Get a list of supported email providers
     *
     * @return array List of supported providers
     */
    public static function getSupportedProviders(): array {
        return [
            'smtp' => 'SMTP Server',
            'sendgrid' => 'SendGrid',
            'ses' => 'Amazon SES'
        ];
    }
} 