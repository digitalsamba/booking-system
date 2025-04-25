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

    // TODO: Add method for handling logo uploads securely (saving file, updating logoUrl)
    // TODO: Consider creating a BrandingSettings Model/Entity class for better structure
} 