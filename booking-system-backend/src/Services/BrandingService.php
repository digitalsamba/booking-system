<?php

namespace App\Services;

// use App\Models\BrandingSettings; // Assuming a BrandingSettings model exists or will be created
use App\Utils\Database; // Use the database utility class
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
// No need for Client or LoggerInterface here now

class BrandingService
{
    private Collection $collection;
    // No logger property needed if using error_log directly

    public function __construct()
    {
        // Get collection via the Database utility class
        $this->collection = Database::getCollection('branding_settings');
        // No logger instantiation needed
    }

    /**
     * Get branding settings for a specific user.
     *
     * @param string $userId The ObjectId of the user.
     * @return array|null Branding settings or null if not found.
     */
    public function getBrandingSettings(string $userId): ?array
    {
        error_log("[BrandingService] Getting settings for userId: " . $userId);
        try {
            $settings = $this->collection->findOne(['userId' => new ObjectId($userId)]);
            error_log("[BrandingService] Raw settings found: " . json_encode($settings)); // Log raw result

            // Convert MongoDB document to array, handle potential null, convert ObjectId back to string if needed
            // TODO: Use a dedicated helper (like MongoDBHelper?) for consistent formatting
            return $settings ? (array) $settings : null; // Basic conversion
        } catch (\Exception $e) {
            error_log('[BrandingService] Error fetching branding settings for user ' . $userId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update or create branding settings for a specific user.
     *
     * @param string $userId The ObjectId of the user.
     * @param array $data Branding data (logoUrl, primaryColor, etc.).
     * @return bool True on success, false on failure.
     */
    public function updateBrandingSettings(string $userId, array $data): bool
    {
        error_log("[BrandingService] Updating settings for userId: " . $userId . " with data: " . json_encode($data));
        try {
            // TODO: Add robust input validation/sanitization here
            $updateData = [
                '$set' => $data + ['updatedAt' => new \MongoDB\BSON\UTCDateTime()] // Merge and add timestamp
            ];
            error_log("[BrandingService] Update operation payload: " . json_encode($updateData)); // Log payload

            $result = $this->collection->updateOne(
                ['userId' => new ObjectId($userId)],
                $updateData,
                ['upsert' => true] // Create if not exists
            );

            $modified = $result->getModifiedCount();
            $upserted = $result->getUpsertedCount();
            error_log("[BrandingService] Update result - Modified: {$modified}, Upserted: {$upserted}"); // Log result counts

            return $modified > 0 || $upserted > 0;
        } catch (\Exception $e) {
            error_log('[BrandingService] Error updating branding settings for user ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handles the logo file upload, validation, storage, and database update.
     *
     * @param string $userId The ObjectId of the user.
     * @param array $file The file data from $_FILES.
     * @return array Result status ['success' => bool, 'logoUrl' => string|null, 'error' => string|null, 'status' => int|null].
     */
    public function handleLogoUpload(string $userId, array $file): array
    {
        error_log("[BrandingService::handleLogoUpload] Handling logo upload for userId: {$userId}");

        // --- Configuration ---
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB
        $baseUploadDir = dirname(__DIR__, 2) . '/public/uploads/branding'; // Path relative to src dir
        $baseUploadUrl = '/uploads/branding'; // Publicly accessible base URL

        // --- Validation ---
        // Check file type
        $fileMimeType = mime_content_type($file['tmp_name']);
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            error_log("[BrandingService::handleLogoUpload] Invalid file type: {$fileMimeType} for user: {$userId}");
            return ['success' => false, 'error' => 'Invalid file type. Allowed types: JPG, PNG, GIF, SVG.', 'status' => 415];
        }

        // Check file size
        if ($file['size'] > $maxFileSize) {
            error_log("[BrandingService::handleLogoUpload] File size exceeds limit ({$file['size']} > {$maxFileSize}) for user: {$userId}");
            return ['success' => false, 'error' => 'File size exceeds limit (5MB).', 'status' => 413];
        }

        // --- File Storage ---
        $userUploadDir = $baseUploadDir . '/' . $userId;
        $userUploadUrl = $baseUploadUrl . '/' . $userId;

        // Create user directory if it doesn't exist
        if (!is_dir($userUploadDir)) {
            if (!mkdir($userUploadDir, 0775, true)) { // Creates recursively, sets permissions
                error_log("[BrandingService::handleLogoUpload] Failed to create upload directory: {$userUploadDir} for user: {$userId}");
                return ['success' => false, 'error' => 'Failed to create storage directory.', 'status' => 500];
            }
            error_log("[BrandingService::handleLogoUpload] Created directory: {$userUploadDir}");
        }

        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFilename = bin2hex(random_bytes(16)) . '.' . strtolower($fileExtension);
        $destinationPath = $userUploadDir . '/' . $safeFilename;
        $logoUrl = $userUploadUrl . '/' . $safeFilename;

        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            error_log("[BrandingService::handleLogoUpload] Failed to move uploaded file from {$file['tmp_name']} to {$destinationPath} for user: {$userId}");
            return ['success' => false, 'error' => 'Failed to save uploaded file.', 'status' => 500];
        }
        error_log("[BrandingService::handleLogoUpload] File moved successfully to: {$destinationPath}");

        // --- Database Update ---
        $updateData = [
            'logoUrl' => $logoUrl,
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        try {
            $result = $this->collection->updateOne(
                ['userId' => new ObjectId($userId)],
                ['$set' => $updateData],
                ['upsert' => true] // Ensure settings doc exists or is created
            );

            $modified = $result->getModifiedCount();
            $upserted = $result->getUpsertedCount();
            error_log("[BrandingService::handleLogoUpload] DB update result - Modified: {$modified}, Upserted: {$upserted}");

            if ($modified > 0 || $upserted > 0) {
                error_log("[BrandingService::handleLogoUpload] Successfully updated logoUrl for user {$userId} to: {$logoUrl}");
                return ['success' => true, 'logoUrl' => $logoUrl];
            } else {
                error_log("[BrandingService::handleLogoUpload] DB update executed but reported no changes for user: {$userId}");
                // Technically successful if file saved, but DB state didn't change
                return ['success' => true, 'logoUrl' => $logoUrl, 'message' => 'File saved, but database record was already up-to-date.'];
            }
        } catch (\Exception $e) {
            error_log('[BrandingService::handleLogoUpload] Error updating database for user ' . $userId . ': ' . $e->getMessage());
            // TODO: Consider deleting the uploaded file if DB update fails?
            return ['success' => false, 'error' => 'Failed to update branding settings database.', 'status' => 500];
        }
    }

    // TODO: Add method for handling logo uploads securely (saving file, updating logoUrl)
    // TODO: Consider creating a BrandingSettings Model/Entity class for better structure
} 