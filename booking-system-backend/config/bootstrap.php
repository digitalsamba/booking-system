<?php
/**
 * Application bootstrap file
 * 
 * Initializes application components
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

// Initialize MongoDB indexes (run this only when needed, not on every request)
if (isset($_SERVER['INITIALIZE_DB']) && $_SERVER['INITIALIZE_DB'] === 'true') {
    \App\Utils\DatabaseInit::initializeIndexes();
    echo "Database indexes initialized successfully.\n";
}