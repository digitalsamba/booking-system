<?php

namespace App\Controllers;

use App\Models\EmailConfigModel;
use App\Utils\Email\EmailServiceFactory;
use App\Utils\JwtAuth;
use App\Utils\Response;

/**
 * Controller for handling email-related functionality
 */
class EmailController extends BaseController {
    /**
     * Email config model
     *
     * @var EmailConfigModel
     */
    private $emailConfigModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->emailConfigModel = new EmailConfigModel();
    }
    
    /**
     * Get email configuration for the authenticated provider
     *
     * @return Response
     */
    public function getEmailConfig(): Response {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return Response::error('Unauthorized', 401);
        }
        
        $config = $this->emailConfigModel->getConfigForProvider($user['id']);
        
        if (!$config) {
            // Return default config from environment
            return Response::success([
                'provider_type' => getenv('EMAIL_PROVIDER') ?: 'smtp',
                'settings' => [
                    'from_email' => getenv('EMAIL_FROM') ?: 'bookings@example.com',
                    'from_name' => getenv('EMAIL_FROM_NAME') ?: 'Booking System'
                ],
                'is_default' => true
            ]);
        }
        
        // Add flag to indicate this is a custom config
        $config['is_default'] = false;
        
        return Response::success($config);
    }
    
    /**
     * Save email configuration for the authenticated provider
     *
     * @return Response
     */
    public function saveEmailConfig(): Response {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return Response::error('Unauthorized', 401);
        }
        
        $data = $this->getJsonData();
        
        // Validate input
        if (!isset($data['provider_type']) || empty($data['provider_type'])) {
            return Response::error('Provider type is required', 400);
        }
        
        // Validate provider type
        $supportedProviders = $this->emailConfigModel->getSupportedProviders();
        if (!array_key_exists($data['provider_type'], $supportedProviders)) {
            return Response::error('Unsupported email provider', 400);
        }
        
        // Create config array
        $config = [
            'provider_type' => $data['provider_type'],
            'settings' => $data['settings'] ?? []
        ];
        
        // Save config
        $success = $this->emailConfigModel->saveConfigForProvider($user['id'], $config);
        
        if (!$success) {
            return Response::error('Failed to save email configuration', 500);
        }
        
        return Response::success(['message' => 'Email configuration saved successfully']);
    }
    
    /**
     * Reset email configuration for the authenticated provider
     *
     * @return Response
     */
    public function resetEmailConfig(): Response {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return Response::error('Unauthorized', 401);
        }
        
        // Delete custom config
        $this->emailConfigModel->deleteConfigForProvider($user['id']);
        
        return Response::success(['message' => 'Email configuration reset to default']);
    }
    
    /**
     * Get list of supported email providers
     *
     * @return Response
     */
    public function getSupportedProviders(): Response {
        return Response::success([
            'providers' => $this->emailConfigModel->getSupportedProviders()
        ]);
    }
    
    /**
     * Send a test email
     *
     * @return Response
     */
    public function sendTestEmail(): Response {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return Response::error('Unauthorized', 401);
        }
        
        $data = $this->getJsonData();
        
        // Validate input
        if (!isset($data['email']) || empty($data['email'])) {
            return Response::error('Email address is required', 400);
        }
        
        // Get user's email config or use default
        $config = $this->emailConfigModel->getConfigForProvider($user['id']);
        $providerType = null;
        $providerConfig = [];
        
        if ($config) {
            $providerType = $config['provider_type'];
            $providerConfig = $config['settings'] ?? [];
        }
        
        try {
            // Create email service
            $emailService = EmailServiceFactory::create($providerType, $providerConfig);
            
            // Check if the email service is configured
            if (!$emailService->isConfigured()) {
                return Response::error('Email service is not configured properly', 400);
            }
            
            // Send test email
            $subject = 'Test Email from Booking System';
            $body = "This is a test email from the Booking System.\n\nTime sent: " . date('Y-m-d H:i:s');
            $htmlBody = "<h1>Test Email</h1><p>This is a test email from the Booking System.</p><p><strong>Time sent:</strong> " . date('Y-m-d H:i:s') . "</p>";
            
            $success = $emailService->send($data['email'], $subject, $body, $htmlBody);
            
            if (!$success) {
                return Response::error('Failed to send test email', 500);
            }
            
            return Response::success(['message' => 'Test email sent successfully']);
        } catch (\Exception $e) {
            return Response::error('Error sending test email: ' . $e->getMessage(), 500);
        }
    }
} 