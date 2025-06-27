// ... existing code ...

// Branding Settings (Requires Auth)
$r->addRoute('GET', '/branding', [BrandingController::class, 'getSettings']);
$r->addRoute('PUT', '/branding', [BrandingController::class, 'updateSettings']);
$r->addRoute('POST', '/branding/logo', [BrandingController::class, 'uploadLogo']); // New route for logo upload

// API routes (Requires Auth and /api prefix handling by proxy/webserver)
$r->addGroup('/api', function (RouteCollector $r) {
    // ... existing API routes ...
});

// Public routes (No Auth Required, /public prefix is removed by proxy)
// Define routes directly without a group
$r->addRoute('GET', '/ping', function() { // Add simple test route
    // Ensure Response class is accessible or use header/echo
    header('Content-Type: application/json');
    echo json_encode(['message' => 'pong']);
    exit;
});
$r->addRoute('GET', '/availability', [PublicController::class, 'availability']);
$r->addRoute('POST', '/booking', [PublicController::class, 'booking']);
$r->addRoute('GET', '/user/{username}', [PublicController::class, 'getProviderDetails']);
$r->addRoute('GET', '/branding/{userId}', [PublicController::class, 'getBrandingSettings']);

// Test routes (if any)
// ... existing code ...