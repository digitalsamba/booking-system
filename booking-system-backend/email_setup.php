<?php
/**
 * Email setup script
 * 
 * This script will:
 * 1. Install the dotenv package if not already installed
 * 2. Set up a proper loader for environment variables
 * 3. Fix any issues with the current configuration
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Email Integration Setup Script\n";
echo "============================\n\n";

// Get current directory
$dir = __DIR__;

// Check if composer.json exists
$composerJson = $dir . '/composer.json';
if (!file_exists($composerJson)) {
    echo "Error: composer.json not found. Make sure you're in the right directory.\n";
    exit(1);
}

// Check if dotenv is already installed
$composer = json_decode(file_get_contents($composerJson), true);
$hasDotenv = isset($composer['require']['vlucas/phpdotenv']);

if (!$hasDotenv) {
    echo "Installing vlucas/phpdotenv package...\n";
    exec('composer require vlucas/phpdotenv', $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "Error installing vlucas/phpdotenv. Please install it manually:\n";
        echo "composer require vlucas/phpdotenv\n";
        exit(1);
    }
    
    echo "vlucas/phpdotenv installed successfully.\n";
} else {
    echo "vlucas/phpdotenv is already installed.\n";
}

// Create a bootstrap file for loading environment variables
$bootstrapFile = $dir . '/bootstrap.php';
$bootstrapContent = <<<'EOT'
<?php
/**
 * Bootstrap file for loading environment variables
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define base paths
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'config');
define('SRC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'src');
EOT;

file_put_contents($bootstrapFile, $bootstrapContent);
echo "Created bootstrap.php for loading environment variables.\n";

// Update router.php to include bootstrap.php
$routerFile = $dir . '/router.php';
if (file_exists($routerFile)) {
    $routerContent = file_get_contents($routerFile);
    
    // Check if router already includes bootstrap.php
    if (strpos($routerContent, 'bootstrap.php') === false) {
        // Find the position where to insert bootstrap.php include
        $pos = strpos($routerContent, "require_once $autoloadPath;");
        if ($pos !== false) {
            // Replace the autoloader include with bootstrap include
            $routerContent = str_replace(
                "require_once $autoloadPath;",
                "require_once __DIR__ . '/bootstrap.php';",
                $routerContent
            );
            
            file_put_contents($routerFile, $routerContent);
            echo "Updated router.php to use bootstrap.php.\n";
        } else {
            echo "Warning: Could not update router.php automatically. Please update it manually to include bootstrap.php.\n";
        }
    } else {
        echo "router.php already includes bootstrap.php.\n";
    }
} else {
    echo "Warning: router.php not found.\n";
}

// Update email_test.php to use bootstrap.php
$testFile = $dir . '/email_test.php';
if (file_exists($testFile)) {
    $testContent = file_get_contents($testFile);
    
    // Check if email_test.php already includes bootstrap.php
    if (strpos($testContent, 'bootstrap.php') === false) {
        // Replace the autoloader include with bootstrap include
        $testContent = str_replace(
            "require __DIR__ . '/vendor/autoload.php';",
            "require __DIR__ . '/bootstrap.php';",
            $testContent
        );
        
        file_put_contents($testFile, $testContent);
        echo "Updated email_test.php to use bootstrap.php.\n";
    } else {
        echo "email_test.php already includes bootstrap.php.\n";
    }
} else {
    echo "Warning: email_test.php not found.\n";
}

echo "\nSetup complete!\n";
echo "Please run 'php email_test.php your-email@example.com sendgrid' to test your SendGrid configuration.\n"; 