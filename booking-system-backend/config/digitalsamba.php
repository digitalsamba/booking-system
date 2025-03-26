<?php
/**
 * Digital Samba API configuration
 */

return [
    'api_base_url' => 'https://api.digitalsamba.com/api/v1',
    'default_settings' => [
        'privacy' => 'public',  // Can be 'public' or 'private'
        // Note: Removed features as they require specific format
        // Note: Removed layout as it might not be supported
        'language' => 'en'  // Simple language code without region
    ]
];