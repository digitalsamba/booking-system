<?php

namespace App\Models;

use App\Utils\Email\EmailServiceFactory;
use MongoDB\BSON\ObjectId;

/**
 * Model for storing email configuration settings
 */
class EmailConfigModel extends BaseModel {
    /**
     * Get collection name
     *
     * @return string
     */
    protected function getCollectionName(): string {
        return 'email_configs';
    }
    
    /**
     * Get the email configuration for a provider
     *
     * @param string $providerId Provider ID
     * @return array|null Email configuration or null if not found
     */
    public function getConfigForProvider(string $providerId): ?array {
        $id = new ObjectId($providerId);
        $config = $this->getCollection()->findOne(['provider_id' => $id]);
        
        if ($config) {
            return $this->formatDocument($config);
        }
        
        return null;
    }
    
    /**
     * Save email configuration for a provider
     *
     * @param string $providerId Provider ID
     * @param array $config Email configuration
     * @return bool Success status
     */
    public function saveConfigForProvider(string $providerId, array $config): bool {
        $id = new ObjectId($providerId);
        
        // Check if config exists
        $existing = $this->getCollection()->findOne(['provider_id' => $id]);
        
        if ($existing) {
            // Update existing config
            $result = $this->getCollection()->updateOne(
                ['provider_id' => $id],
                ['$set' => [
                    'provider_type' => $config['provider_type'],
                    'settings' => $config['settings'],
                    'updated_at' => new \MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            return $result->getModifiedCount() > 0;
        } else {
            // Create new config
            $result = $this->getCollection()->insertOne([
                'provider_id' => $id,
                'provider_type' => $config['provider_type'],
                'settings' => $config['settings'],
                'created_at' => new \MongoDB\BSON\UTCDateTime(),
                'updated_at' => new \MongoDB\BSON\UTCDateTime()
            ]);
            
            return $result->getInsertedCount() > 0;
        }
    }
    
    /**
     * Delete email configuration for a provider
     *
     * @param string $providerId Provider ID
     * @return bool Success status
     */
    public function deleteConfigForProvider(string $providerId): bool {
        $id = new ObjectId($providerId);
        $result = $this->getCollection()->deleteOne(['provider_id' => $id]);
        
        return $result->getDeletedCount() > 0;
    }
    
    /**
     * Get supported email providers
     *
     * @return array List of supported providers
     */
    public function getSupportedProviders(): array {
        return EmailServiceFactory::getSupportedProviders();
    }
    
    /**
     * Format document for API response
     *
     * @param array $document Document from MongoDB
     * @return array Formatted document
     */
    protected function formatDocument($document): array {
        if (!$document) {
            return [];
        }
        
        return [
            'id' => (string)$document['_id'],
            'provider_id' => (string)$document['provider_id'],
            'provider_type' => $document['provider_type'],
            'settings' => $document['settings'] ?? [],
            'created_at' => $this->formatDate($document['created_at'] ?? null),
            'updated_at' => $this->formatDate($document['updated_at'] ?? null)
        ];
    }
} 