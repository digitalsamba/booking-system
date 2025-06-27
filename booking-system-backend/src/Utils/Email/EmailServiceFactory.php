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
                // Read API key directly from .env file to avoid truncation
                error_log("EmailServiceFactory: Reading SendGrid API key directly from .env file");
                
                // Only read from .env if not provided in config
                if (!isset($config['api_key'])) {
                    $envFile = dirname(dirname(dirname(__DIR__))) . '/.env';
                    if (file_exists($envFile)) {
                        $content = file_get_contents($envFile);
                        $matches = [];
                        if (preg_match('/SENDGRID_API_KEY\s*=\s*[\'"]*([^\'"\n]+)[\'"]*/i', $content, $matches)) {
                            $config['api_key'] = trim($matches[1]);
                            error_log("EmailServiceFactory: Successfully read SendGrid API key from .env file (length: " . strlen($config['api_key']) . ")");
                        } else {
                            error_log("EmailServiceFactory: Could not find SENDGRID_API_KEY in .env file");
                        }
                    } else {
                        error_log("EmailServiceFactory: .env file not found at $envFile");
                    }
                }
                
                // Fall back to EmailConfig if direct reading failed
                if (!isset($config['api_key']) && EmailConfig::get('SENDGRID_API_KEY')) {
                    error_log("EmailServiceFactory: Falling back to EmailConfig for SendGrid API key");
                    $config['api_key'] = EmailConfig::get('SENDGRID_API_KEY');
                }
                
                // Log if we still don't have an API key
                if (!isset($config['api_key'])) {
                    error_log("EmailServiceFactory: ERROR - SendGrid API key NOT found after all attempts");
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