<?php
require_once __DIR__ . '/env.php';

// Security: Error display based on environment
$env = getenv('APP_ENV') ?: 'production';
$showErrors = ($env === 'development') ? 1 : 0;
ini_set('display_errors', $showErrors);
error_reporting(E_ALL);

// Start session with secure settings
$https_enabled = getenv('HTTPS') === 'true' || isset($_SERVER['HTTPS']);
session_start([
    'cookie_secure' => $https_enabled, // Only send over HTTPS
    'cookie_httponly' => true, // Prevent JavaScript access
    'cookie_samesite' => 'Strict', // CSRF protection
    'cookie_lifetime' => 3600 * 24, // 24 hours
    'cookie_path' => '/', // Available site-wide
    'gc_maxlifetime' => 3600 * 24 // Garbage collection
]);

// Include database connection
require_once 'db.php';
?>
