<?php

namespace App\Controllers;

use App\Services\BrandingService;
use App\Utils\Response; // Use the Response utility consistent with BaseController
use App\Utils\MongoDBHelper; // Import the helper

class BrandingController extends BaseController // Extend BaseController
{
    private BrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = new BrandingService();
    }

    /**
     * Get the branding settings for the authenticated user.
     */
    public function getSettings(): void // Changed return type to void as response is handled internally
    {
        try {
            $userId = $this->getUserId(); 
            if (!$userId) {
                Response::json(['error' => 'Unauthorized'], 401);
                return;
            }

            $rawSettings = $this->brandingService->getBrandingSettings($userId);

            if (!$rawSettings) {
                Response::json(['message' => 'Branding settings not found'], 404);
                return;
            }

            // Format the settings using the helper
            $settings = MongoDBHelper::formatForApi($rawSettings);

            // Manually ensure userId (if present) is a string, as formatForApi doesn't handle it
            if (isset($settings['userId']) && $settings['userId'] instanceof \MongoDB\BSON\ObjectId) {
                 $settings['userId'] = (string) $settings['userId'];
            }
            // formatForApi already handles _id -> id conversion
            // if (isset($settings['_id']) && $settings['_id'] instanceof \MongoDB\BSON\ObjectId) {
            //     $settings['_id'] = (string) $settings['_id'];
            // }

            Response::json($settings); // Send formatted settings
        } catch (\Exception $e) {
            error_log('Error in BrandingController::getSettings: ' . $e->getMessage());
            Response::json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Update the branding settings for the authenticated user.
     */
    public function updateSettings(): void // Changed return type to void
    {
        try {
            // Use getUserId() from BaseController
            $userId = $this->getUserId();
            if (!$userId) {
                Response::json(['error' => 'Unauthorized'], 401);
                return;
            }

            // Use getJsonData() from BaseController
            $data = $this->getJsonData();
            if (empty($data)) {
                Response::json(['error' => 'Invalid input'], 400);
                return;
            }

            // Basic validation (more robust validation should be in the service or model)
            // TODO: Move validation logic to service/model
            $allowedFields = ['logoUrl', 'primaryColor', 'secondaryColor', 'backgroundColor', 'textColor', 'fontFamily', 'customCss'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                 Response::json(['error' => 'No valid fields provided for update'], 400);
                 return;
            }

            $success = $this->brandingService->updateBrandingSettings($userId, $updateData);

            if ($success) {
                Response::json(['message' => 'Branding settings updated successfully']);
            } else {
                // Logged in service
                Response::json(['error' => 'Failed to update branding settings'], 500);
            }
        } catch (\Exception $e) {
            error_log('Error in BrandingController::updateSettings: ' . $e->getMessage());
            Response::json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Handle logo upload for the authenticated user.
     */
    public function uploadLogo(): void
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                Response::json(['error' => 'Unauthorized'], 401);
                return;
            }

            // Check if file was uploaded
            if (empty($_FILES['logoFile'])) {
                error_log("[BrandingController::uploadLogo] No logoFile found in _FILES for user: {$userId}");
                Response::json(['error' => "No file uploaded or incorrect field name ('logoFile' expected)."], 400);                return;
            }

            $file = $_FILES['logoFile'];
            error_log("[BrandingController::uploadLogo] Received file: " . json_encode($file));

            // Basic check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                error_log("[BrandingController::uploadLogo] File upload error code: {$file['error']} for user: {$userId}");
                Response::json(['error' => 'File upload failed with error code: ' . $file['error']], 500);
                return;
            }

            $result = $this->brandingService->handleLogoUpload($userId, $file);

            if ($result['success']) {
                Response::json(['message' => 'Logo uploaded successfully', 'logoUrl' => $result['logoUrl']]);
            } else {
                Response::json(['error' => $result['error']], $result['status'] ?? 500);
            }
        } catch (\Exception $e) {
            error_log('[BrandingController::uploadLogo] Error: ' . $e->getMessage());
            Response::json(['error' => 'Internal Server Error'], 500);
        }
    }

    // TODO: Add endpoint for handling logo uploads (POST /api/branding/logo ?)
} 