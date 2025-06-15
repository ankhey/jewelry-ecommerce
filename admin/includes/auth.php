<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /Glamour-shopv1.3/admin/login.php');
        exit;
    }

    // Verify admin exists in database
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT id, name, email FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            // Admin not found in database, clear session and redirect
            session_destroy();
            header('Location: /Glamour-shopv1.3/admin/login.php');
            exit;
        }

        // Store admin name in session if not already set
        if (!isset($_SESSION['admin_name'])) {
            $_SESSION['admin_name'] = $admin['name'];
        }
    } catch (PDOException $e) {
        error_log('Error verifying admin: ' . $e->getMessage());
        // Continue even if DB check fails
    }
}

// Check if user has specific permission
function hasPermission($permission) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // For now, grant all permissions to logged-in admins
    // You can implement more granular permission checks here later
    return true;
}

// Get current admin user
function getCurrentAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting admin details: ' . $e->getMessage());
        return null;
    }
}

// Function to require specific permission
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        header('Location: /Glamour-shopv1.3/admin/dashboard.php');
        exit();
    }
} 