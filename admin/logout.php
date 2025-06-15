<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

// Redirect to login page
header('Location: /Glamour-shopv1.3/admin/login.php');
exit(); 