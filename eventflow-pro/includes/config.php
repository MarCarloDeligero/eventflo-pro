<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'eventflow_pro');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'EventFlow Pro');
define('SITE_URL', 'http://localhost/eventflow-pro');
define('SITE_EMAIL', 'noreply@eventflow.com');

// Path configuration
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Security
define('ENCRYPTION_KEY', 'your-secure-key-here'); // Change this!

// Timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $file = ROOT_PATH . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include common functions
require_once ROOT_PATH . '/includes/functions.php';
?>