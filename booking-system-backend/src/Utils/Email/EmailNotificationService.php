<?php

namespace App\Utils\Email;

use App\Models\EmailConfigModel;
use App\Models\BookingModel;
use App\Models\UserModel;
use MongoDB\BSON\ObjectId;

/**
 * Service for sending email notifications
 */
class EmailNotificationService {
    /**
     * Email config model
     *
     * @var EmailConfigModel
     */
    private $emailConfigModel;
    
    /**
     * Booking model
     *
     * @var BookingModel
     */
    private $bookingModel;
    
    /**
     * User model
     *
     * @var UserModel
     */
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->emailConfigModel = new EmailConfigModel();
        $this->bookingModel = new BookingModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Send booking confirmation email to the customer
     *
     * @param string $bookingId Booking ID
     * @return bool Success status
     */
    public function sendBookingConfirmation(string $bookingId): bool {
        try {
            // Get booking details
            $booking = $this->bookingModel->getById($bookingId);
            
            if (!$booking) {
                error_log("Booking not found: {$bookingId}");
                return false;
            }
            
            // Get provider details
            $provider = $this->userModel->getById($booking['provider_id']);
            
            if (!$provider) {
                error_log("Provider not found: {$booking['provider_id']}");
                return false;
            }
            
            // Get customer email
            $customerEmail = $booking['customer']['email'] ?? null;
            
            if (!$customerEmail) {
                error_log("Customer email not found for booking: {$bookingId}");
                return false;
            }
            
            // Get email service for provider
            $emailService = $this->getEmailServiceForProvider($provider['id']);
            
            // Format dates
            $bookingDate = date('l, F j, Y', strtotime($booking['start_time']));
            $startTime = date('g:i A', strtotime($booking['start_time']));
            $endTime = date('g:i A', strtotime($booking['end_time']));
            
            // Prepare template variables
            $templateVars = [
                'customer_name' => $booking['customer']['name'] ?? 'Valued Customer',
                'provider_name' => $provider['display_name'] ?? $provider['username'],
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'booking_id' => $bookingId,
                'customer_link' => $booking['customer']['customer_link'] ?? null,
                'notes' => $booking['notes'] ?? '',
                'company_name' => getenv('EMAIL_FROM_NAME') ?: 'Booking System'
            ];
            
            // Send email
            $subject = "Booking Confirmation: {$bookingDate} at {$startTime}";
            return $emailService->sendTemplate(
                $customerEmail,
                $subject,
                'booking_confirmation',
                $templateVars
            );
            
        } catch (\Exception $e) {
            error_log("Error sending booking confirmation email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking notification email to the provider
     *
     * @param string $bookingId Booking ID
     * @return bool Success status
     */
    public function sendBookingNotification(string $bookingId): bool {
        try {
            // Get booking details
            $booking = $this->bookingModel->getById($bookingId);
            
            if (!$booking) {
                error_log("Booking not found: {$bookingId}");
                return false;
            }
            
            // Get provider details
            $provider = $this->userModel->getById($booking['provider_id']);
            
            if (!$provider) {
                error_log("Provider not found: {$booking['provider_id']}");
                return false;
            }
            
            // Get provider email
            $providerEmail = $provider['email'] ?? null;
            
            if (!$providerEmail) {
                error_log("Provider email not found: {$booking['provider_id']}");
                return false;
            }
            
            // Get email service for system
            $emailService = $this->getEmailServiceForSystem();
            
            // Format dates
            $bookingDate = date('l, F j, Y', strtotime($booking['start_time']));
            $startTime = date('g:i A', strtotime($booking['start_time']));
            $endTime = date('g:i A', strtotime($booking['end_time']));
            
            // Prepare template variables
            $templateVars = [
                'provider_name' => $provider['display_name'] ?? $provider['username'],
                'customer_name' => $booking['customer']['name'] ?? 'Unknown Customer',
                'customer_email' => $booking['customer']['email'] ?? 'No email provided',
                'customer_phone' => $booking['customer']['phone'] ?? 'No phone provided',
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'booking_id' => $bookingId,
                'provider_link' => $booking['provider_link'] ?? null,
                'notes' => $booking['notes'] ?? '',
                'company_name' => getenv('EMAIL_FROM_NAME') ?: 'Booking System'
            ];
            
            // Send email
            $subject = "New Booking: {$templateVars['customer_name']} on {$bookingDate}";
            return $emailService->sendTemplate(
                $providerEmail,
                $subject,
                'booking_notification',
                $templateVars
            );
            
        } catch (\Exception $e) {
            error_log("Error sending booking notification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking reminder email to the customer
     *
     * @param string $bookingId Booking ID
     * @return bool Success status
     */
    public function sendBookingReminder(string $bookingId): bool {
        try {
            // Get booking details
            $booking = $this->bookingModel->getById($bookingId);
            
            if (!$booking) {
                error_log("Booking not found: {$bookingId}");
                return false;
            }
            
            // Get provider details
            $provider = $this->userModel->getById($booking['provider_id']);
            
            if (!$provider) {
                error_log("Provider not found: {$booking['provider_id']}");
                return false;
            }
            
            // Get customer email
            $customerEmail = $booking['customer']['email'] ?? null;
            
            if (!$customerEmail) {
                error_log("Customer email not found for booking: {$bookingId}");
                return false;
            }
            
            // Get email service for provider
            $emailService = $this->getEmailServiceForProvider($provider['id']);
            
            // Format dates
            $bookingDate = date('l, F j, Y', strtotime($booking['start_time']));
            $startTime = date('g:i A', strtotime($booking['start_time']));
            $endTime = date('g:i A', strtotime($booking['end_time']));
            
            // Prepare template variables
            $templateVars = [
                'customer_name' => $booking['customer']['name'] ?? 'Valued Customer',
                'provider_name' => $provider['display_name'] ?? $provider['username'],
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'booking_id' => $bookingId,
                'customer_link' => $booking['customer']['customer_link'] ?? null,
                'notes' => $booking['notes'] ?? '',
                'company_name' => getenv('EMAIL_FROM_NAME') ?: 'Booking System'
            ];
            
            // Send email
            $subject = "Reminder: Your booking on {$bookingDate} at {$startTime}";
            return $emailService->sendTemplate(
                $customerEmail,
                $subject,
                'booking_reminder',
                $templateVars
            );
            
        } catch (\Exception $e) {
            error_log("Error sending booking reminder email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking cancellation email to the customer
     *
     * @param string $bookingId Booking ID
     * @param array $bookingData Booking data if the booking is already deleted
     * @return bool Success status
     */
    public function sendBookingCancellation(string $bookingId, array $bookingData = null): bool {
        try {
            // Get booking details
            $booking = $bookingData;
            
            if (!$booking) {
                $booking = $this->bookingModel->getById($bookingId);
            }
            
            if (!$booking) {
                error_log("Booking not found: {$bookingId}");
                return false;
            }
            
            // Get provider details
            $provider = $this->userModel->getById($booking['provider_id']);
            
            if (!$provider) {
                error_log("Provider not found: {$booking['provider_id']}");
                return false;
            }
            
            // Get customer email
            $customerEmail = $booking['customer']['email'] ?? null;
            
            if (!$customerEmail) {
                error_log("Customer email not found for booking: {$bookingId}");
                return false;
            }
            
            // Get email service for provider
            $emailService = $this->getEmailServiceForProvider($provider['id']);
            
            // Format dates
            $bookingDate = date('l, F j, Y', strtotime($booking['start_time']));
            $startTime = date('g:i A', strtotime($booking['start_time']));
            
            // Prepare template variables
            $templateVars = [
                'customer_name' => $booking['customer']['name'] ?? 'Valued Customer',
                'provider_name' => $provider['display_name'] ?? $provider['username'],
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'booking_id' => $bookingId,
                'company_name' => getenv('EMAIL_FROM_NAME') ?: 'Booking System'
            ];
            
            // Send email
            $subject = "Booking Cancellation: {$bookingDate} at {$startTime}";
            return $emailService->sendTemplate(
                $customerEmail,
                $subject,
                'booking_cancellation',
                $templateVars
            );
            
        } catch (\Exception $e) {
            error_log("Error sending booking cancellation email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email service for a specific provider
     *
     * @param string $providerId Provider ID
     * @return EmailService
     */
    private function getEmailServiceForProvider(string $providerId): EmailService {
        // Get provider's email config
        $config = $this->emailConfigModel->getConfigForProvider($providerId);
        
        if ($config) {
            // Use provider's custom config
            return EmailServiceFactory::create($config['provider_type'], $config['settings'] ?? []);
        }
        
        // Use system default
        return $this->getEmailServiceForSystem();
    }
    
    /**
     * Get email service for the system
     *
     * @return EmailService
     */
    private function getEmailServiceForSystem(): EmailService {
        // Use system default config from environment
        return EmailServiceFactory::create();
    }
} 