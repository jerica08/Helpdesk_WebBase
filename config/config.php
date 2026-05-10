<?php
// Session Configuration
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'helpdesk_system');

// Application Configuration
define('APP_NAME', 'School Helpdesk System');
define('APP_URL', 'http://localhost/Helpdesk_Web/');
define('UPLOAD_PATH', 'uploads/');

// Security Configuration
define('HASH_COST', 12);
define('SESSION_LIFETIME', 86400); // 24 hours

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $paths = [
        'models/',
        'controllers/',
        'helpers/',
        'config/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>
