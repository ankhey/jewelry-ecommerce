<?php

// Define the base path of the application
define('BASE_PATH', __DIR__ . '/');

// Get the requested URI and trim the base directory if necessary
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove the script name from the request URI to get the path relative to the index.php
$path = str_replace($script_name, '', $request_uri);

// Clean up the path - remove leading/trailing slashes and decode
$path = trim($path, '/');
$path = urldecode($path);

// Simple routing logic
if (empty($path)) {
    // If no path is specified, show the main page
    require_once BASE_PATH . 'public/index.php';
    exit;
}

// Check if the request is for the admin area
if (strpos($path, 'admin/') === 0 || $path === 'admin') {
    // Remove the 'admin/' prefix to get the admin internal path
    $admin_path = $path === 'admin' ? 'index.php' : str_replace('admin/', '', $path);
    $target_file = BASE_PATH . 'admin/' . $admin_path;

    // Simple security check: prevent directory traversal
    if (strpos($admin_path, '../') === false && file_exists($target_file)) {
        // Include the requested admin file
        require_once $target_file;
    } else {
        // Handle not found for admin files
        http_response_code(404);
        echo 'Admin page not found.';
    }
} else {
    // Request is for the public area
    $target_file = BASE_PATH . 'public/' . $path;
    
    // Check if the file exists with .php extension
    if (file_exists($target_file . '.php')) {
        require_once $target_file . '.php';
    } else if (file_exists($target_file)) {
        require_once $target_file;
    } else {
        // Handle not found for public files
        http_response_code(404);
        echo 'Page not found.';
    }
}

?> 