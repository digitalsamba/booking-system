<?php
// syntax_check.php
$files = [
    __DIR__ . '/src/controllers/BaseController.php',
    __DIR__ . '/src/models/BaseModel.php'
];

foreach ($files as $file) {
    echo "Checking {$file}... ";
    exec("php -l " . escapeshellarg($file), $output, $return_var);
    
    if ($return_var === 0) {
        echo "OK\n";
    } else {
        echo "ERRORS DETECTED!\n";
        echo implode("\n", $output) . "\n";
    }
}