<?php

/**
 * Application Route Definitions for FastRoute
 */

use FastRoute\RouteCollector;
// Ensure necessary controllers are imported
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\BookingController;
use App\Controllers\AvailabilityController;
use App\Controllers\DigitalSambaController;
use App\Controllers\BrandingController;
use App\Controllers\PublicController;
use App\Controllers\DebugController;
use App\Controllers\EmailController;

// The file MUST return a function that accepts a RouteCollector
return function(RouteCollector $r) {

    // Test route
    $r->addRoute('GET', '/test', function() {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'message' => 'Test route works!']);
        exit;
    });
    
    $r->addRoute('GET', '/ping', function() { 
        header('Content-Type: application/json');
        echo json_encode(['message' => 'pong']);
        exit;
    });

    // --- Define Public Routes Group ---
    // (For frontend requests with /public prefix)
    $r->addGroup('/public', function (RouteCollector $r) {
        $r->addRoute('GET', '/availability', [PublicController::class, 'availability']);
        $r->addRoute('POST', '/booking', [PublicController::class, 'booking']);
        $r->addRoute('GET', '/branding/{userId}', [PublicController::class, 'getBrandingSettings']);
        $r->addRoute('GET', '/user/{username}', [PublicController::class, 'getProviderDetails']);
    });

    // --- Define all routes without prefix (for when proxy strips /api) ---
    
    // Auth Routes
    $r->addRoute('POST', '/auth/login', [AuthController::class, 'login']);
    $r->addRoute('POST', '/auth/register', [AuthController::class, 'register']);
    $r->addRoute('POST', '/auth/new-token', [AuthController::class, 'newToken']);
    $r->addRoute('GET', '/auth/profile', [AuthController::class, 'getProfile']);
    $r->addRoute(['PUT', 'POST'], '/auth/profile', [AuthController::class, 'updateProfile']);
    
    // User Profile - specific routes before dynamic ones
    $r->addRoute('GET', '/user/profile', [UserController::class, 'getProfile']);
    $r->addRoute('PUT', '/user/profile', [UserController::class, 'updateProfile']);
    
    // Availability
    $r->addRoute('GET', '/availability', [AvailabilityController::class, 'index']);
    $r->addRoute('POST', '/availability/slots', [AvailabilityController::class, 'addSlots']);
    $r->addRoute('DELETE', '/availability/slots/{id:[0-9a-fA-F]+}', [AvailabilityController::class, 'deleteSlot']);
    $r->addRoute('DELETE', '/availability/slots', [AvailabilityController::class, 'deleteAllSlots']);
    $r->addRoute('POST', '/availability/generate', [AvailabilityController::class, 'generate']);
    $r->addRoute('DELETE', '/availability/deleteSlot', [AvailabilityController::class, 'deleteSlot']);
    $r->addRoute('DELETE', '/availability/deleteAll', [AvailabilityController::class, 'deleteAll']);
    
    // Bookings
    $r->addRoute('GET', '/bookings', [BookingController::class, 'index']);
    $r->addRoute('GET', '/bookings/{id:[0-9a-fA-F]+}', [BookingController::class, 'view']); 
    $r->addRoute('DELETE', '/bookings/{id:[0-9a-fA-F]+}', [BookingController::class, 'cancel']);
    
    // Digital Samba
    $r->addRoute('GET', '/digitalsamba/rooms', [DigitalSambaController::class, 'listRooms']);
    $r->addRoute('POST', '/digitalsamba/sessions', [DigitalSambaController::class, 'createSession']);
    $r->addRoute('GET', '/digitalsamba/links/{bookingId:[0-9a-fA-F]+}', [DigitalSambaController::class, 'getMeetingLinks']);
    $r->addRoute('POST', '/digitalsamba/generate-links/{bookingId:[0-9a-fA-F]+}', [DigitalSambaController::class, 'generateMeetingLinks']);
    
    // Branding
    $r->addRoute('GET', '/branding', [BrandingController::class, 'getSettings']);
    $r->addRoute('PUT', '/branding', [BrandingController::class, 'updateSettings']);
    $r->addRoute('POST', '/branding/logo', [BrandingController::class, 'uploadLogo']);
    
    // Email
    $r->addRoute('GET', '/email/config', [EmailController::class, 'getConfig']);
    $r->addRoute('POST', '/email/config', [EmailController::class, 'updateConfig']);

    // Debug
    $r->addRoute('GET', '/debug/phpinfo', [DebugController::class, 'phpinfo']);
    $r->addRoute('GET', '/debug/env', [DebugController::class, 'env']);
    $r->addRoute('GET', '/debug/db', [DebugController::class, 'dbStatus']);
    
    // --- Define all routes WITH /api prefix (for direct backend access) ---
    $r->addGroup('/api', function (RouteCollector $r) {
        // Auth Routes
        $r->addRoute('POST', '/auth/login', [AuthController::class, 'login']);
        $r->addRoute('POST', '/auth/register', [AuthController::class, 'register']);
        $r->addRoute('POST', '/auth/new-token', [AuthController::class, 'newToken']);
        $r->addRoute('GET', '/auth/profile', [AuthController::class, 'getProfile']);
        $r->addRoute(['PUT', 'POST'], '/auth/profile', [AuthController::class, 'updateProfile']);
        
        // User Profile
        $r->addRoute('GET', '/user/profile', [UserController::class, 'getProfile']);
        $r->addRoute('PUT', '/user/profile', [UserController::class, 'updateProfile']);
        
        // Availability
        $r->addRoute('GET', '/availability', [AvailabilityController::class, 'index']);
        $r->addRoute('POST', '/availability/slots', [AvailabilityController::class, 'addSlots']);
        $r->addRoute('DELETE', '/availability/slots/{id:[0-9a-fA-F]+}', [AvailabilityController::class, 'deleteSlot']);
        $r->addRoute('DELETE', '/availability/slots', [AvailabilityController::class, 'deleteAllSlots']);
        $r->addRoute('POST', '/availability/generate', [AvailabilityController::class, 'generate']);
        $r->addRoute('DELETE', '/availability/deleteSlot', [AvailabilityController::class, 'deleteSlot']);
        $r->addRoute('DELETE', '/availability/deleteAll', [AvailabilityController::class, 'deleteAll']);
        
        // Bookings
        $r->addRoute('GET', '/bookings', [BookingController::class, 'index']);
        $r->addRoute('GET', '/bookings/{id:[0-9a-fA-F]+}', [BookingController::class, 'view']); 
        $r->addRoute('DELETE', '/bookings/{id:[0-9a-fA-F]+}', [BookingController::class, 'cancel']);
        
        // Digital Samba
        $r->addRoute('GET', '/digitalsamba/rooms', [DigitalSambaController::class, 'listRooms']);
        $r->addRoute('POST', '/digitalsamba/sessions', [DigitalSambaController::class, 'createSession']);
        $r->addRoute('GET', '/digitalsamba/links/{bookingId:[0-9a-fA-F]+}', [DigitalSambaController::class, 'getMeetingLinks']);
        $r->addRoute('POST', '/digitalsamba/generate-links/{bookingId:[0-9a-fA-F]+}', [DigitalSambaController::class, 'generateMeetingLinks']);
        
        // Branding
        $r->addRoute('GET', '/branding', [BrandingController::class, 'getSettings']);
        $r->addRoute('PUT', '/branding', [BrandingController::class, 'updateSettings']);
        $r->addRoute('POST', '/branding/logo', [BrandingController::class, 'uploadLogo']);
        
        // Email
        $r->addRoute('GET', '/email/config', [EmailController::class, 'getConfig']);
        $r->addRoute('POST', '/email/config', [EmailController::class, 'updateConfig']);

        // Debug
        $r->addRoute('GET', '/debug/phpinfo', [DebugController::class, 'phpinfo']);
        $r->addRoute('GET', '/debug/env', [DebugController::class, 'env']);
        $r->addRoute('GET', '/debug/db', [DebugController::class, 'dbStatus']);
    });
    
    // Dynamic routes - MUST come last to avoid shadowing other routes
    $r->addRoute('POST', '/booking', [PublicController::class, 'booking']);
    $r->addRoute('GET', '/branding/{userId}', [PublicController::class, 'getBrandingSettings']);
    $r->addRoute('GET', '/user/{username}', [PublicController::class, 'getProviderDetails']);

}; // End of the main function returned by the file
