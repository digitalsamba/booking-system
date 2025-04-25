<?php

/**
 * Application Route Definitions for FastRoute
 */

use App\Controllers\AuthController;
use App\Controllers\AvailabilityController;
use App\Controllers\BookingController;
use App\Controllers\BrandingController;
use App\Controllers\DigitalSambaController;
use App\Controllers\EmailController;
use App\Controllers\PublicController;

return function(FastRoute\RouteCollector $r) {
    // --- API Routes (Assumed to be prefixed with /api by proxy/webserver) ---

    // Authentication
    $r->addRoute('POST', 'auth/login', [AuthController::class, 'login']);
    $r->addRoute('POST', 'auth/register', [AuthController::class, 'register']);
    $r->addRoute('POST', 'auth/new-token', [AuthController::class, 'newToken']); // Test token generation
    $r->addRoute('GET', 'auth/profile', [AuthController::class, 'getProfile']);
    $r->addRoute(['PUT', 'POST'], 'auth/profile', [AuthController::class, 'updateProfile']);
    // Compatibility route for frontend
    $r->addRoute('GET', 'user/profile', [AuthController::class, 'getProfile']);
    $r->addRoute(['PUT', 'POST'], 'user/profile', [AuthController::class, 'updateProfile']);

    // Availability (Provider authenticated)
    $r->addRoute('GET', 'availability', [AvailabilityController::class, 'index']);
    $r->addRoute('POST', 'availability', [AvailabilityController::class, 'set']); // For adding slots
    // Note: deleteSlot expects ID via query param, not path param in current implementation
    $r->addRoute('DELETE', 'availability/deleteSlot', [AvailabilityController::class, 'deleteSlot']);
    // TODO: Add routes for generate, deleteAll, getSlot(id), updateSlot(id) if needed via RESTful paths
    $r->addRoute('POST', 'availability/generate', [AvailabilityController::class, 'generate']);
    // $r->addRoute('DELETE', 'availability', [AvailabilityController::class, 'deleteAll']);
    // $r->addRoute('GET', 'availability/{id:[0-9a-fA-F]+}', [AvailabilityController::class, 'getSlot']);
    // $r->addRoute('PUT', 'availability/{id:[0-9a-fA-F]+}', [AvailabilityController::class, 'updateSlot']);

    // Bookings (Provider authenticated, except public create)
    $r->addRoute('GET', 'bookings', [BookingController::class, 'index']);
    $r->addRoute('POST', 'booking/create', [BookingController::class, 'create']); // Public booking creation
    // Assuming view() handles GET /booking/{id}
    $r->addRoute('GET', 'booking/{id:[0-9a-fA-F]+}', [BookingController::class, 'view']); 
    // Assuming cancel() handles DELETE /booking/{id}/cancel (or maybe DELETE /booking/{id})
    $r->addRoute('DELETE', 'booking/{id:[0-9a-fA-F]+}/cancel', [BookingController::class, 'cancel']);
    // Note: BookingController also has a details() method, potentially for GET /booking/{id}?
    // If view() is correct, details() might be redundant or for a different purpose.
    // $r->addRoute('GET', 'booking/{id:[0-9a-fA-F]+}/details', [BookingController::class, 'details']);
    
    // Digital Samba Meeting Links (Provider authenticated?)
    $r->addRoute('GET', 'booking/{id:[0-9a-fA-F]+}/meeting-links', [DigitalSambaController::class, 'getMeetingLinks']);
    $r->addRoute('POST', 'booking/{id:[0-9a-fA-F]+}/meeting-links', [DigitalSambaController::class, 'generateMeetingLinks']);

    // Branding Settings (Provider authenticated)
    $r->addRoute('GET', 'branding', [BrandingController::class, 'getSettings']);
    $r->addRoute('PUT', 'branding', [BrandingController::class, 'updateSettings']);
    // TODO: Add POST /branding/logo for upload

    // Email Configuration (Provider authenticated?)
    $r->addRoute('GET', 'email/config', [EmailController::class, 'getConfig']);
    $r->addRoute('POST', 'email/config', [EmailController::class, 'updateConfig']);

    // --- Public Routes (No authentication needed, potentially different prefix handled by webserver/proxy) ---
    // Note: These assume the webserver routes them without the /api prefix
    // If they *do* come through /api, they need to be defined above.
    // We might need clarification on how public/ vs api/ is intended to be routed.
    // For now, defining them as they were in the old router.

    $r->addRoute('GET', 'public/availability', [PublicController::class, 'availability']);
    $r->addRoute('POST', 'public/booking', [PublicController::class, 'booking']); // Assuming POST for public booking submission
    $r->addRoute('GET', 'public/provider/{username}', [PublicController::class, 'getProviderDetails']);

    // --- Simple Test/Ping Routes ---
    $r->addRoute('GET', 'ping', function() {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'message' => 'API is running via FastRoute']);
    });
    $r->addRoute('GET', 'test', function() {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'message' => 'Test route via FastRoute']);
    });
};