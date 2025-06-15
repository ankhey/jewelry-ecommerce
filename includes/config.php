<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration - MUST be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'sql.infinityfree.com'); // Infinity Free database host
define('DB_NAME', 'if0_39101539_glamour_db'); // Your Infinity Free database name
define('DB_USER', 'if0_39101539_glamour_db'); // Your Infinity Free database username
define('DB_PASS', ''); // Your Infinity Free database password

// Site configuration
define('SITE_NAME', 'Glamour Shop');
define('SITE_URL', 'http://glamourstore.great-site.net');

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('PRODUCTS_UPLOAD_DIR', UPLOAD_DIR . '/products');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(PRODUCTS_UPLOAD_DIR)) {
    mkdir(PRODUCTS_UPLOAD_DIR, 0777, true);
}

// Time zone
date_default_timezone_set('Africa/Nairobi');

// Application configuration
define('ADMIN_EMAIL', 'admin@glamourshop.com');

// Include common functions
require_once __DIR__ . '/functions.php';

// Define upload paths
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('PRODUCT_IMAGE_PATH', UPLOAD_PATH . '/products');

// Ensure upload directories exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
if (!file_exists(PRODUCT_IMAGE_PATH)) {
    mkdir(PRODUCT_IMAGE_PATH, 0777, true);
} 