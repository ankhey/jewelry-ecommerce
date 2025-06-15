<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Database connection function
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            // Get database configuration
            $host = DB_HOST;
            $dbname = DB_NAME;
            $username = DB_USER;
            $password = DB_PASS;
            
            // Create PDO instance
            $db = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Could not connect to the database. Please try again later.');
        }
    }
    
    return $db;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    return true;
}

/**
 * Format date to a readable format
 */
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Format price with currency
 */
function formatPrice($price) {
    return 'KES ' . number_format($price, 2);
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    return match($status) {
        'active', 'completed' => 'success',
        'pending' => 'warning',
        'processing' => 'info',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}

/**
 * Sanitize output
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Redirect with message
 */
function redirectWith($url, $message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
    header("Location: $url");
    exit();
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching current user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check if string starts with substring
 */
function startsWith($haystack, $needle) {
    return strpos($haystack, $needle) === 0;
}

/**
 * Get site URL
 */
function getSiteURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
}

/**
 * Get asset URL
 */
function asset($path) {
    return getSiteURL() . '/' . ltrim($path, '/');
}

/**
 * Log error message
 */
function logError($message, $context = []) {
    error_log(sprintf(
        "[%s] %s %s",
        date('Y-m-d H:i:s'),
        $message,
        !empty($context) ? json_encode($context) : ''
    ));
}

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function sanitizeOutput($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime($date) {
    return date('M j, Y g:i A', strtotime($date));
}

function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    header("Location: $url");
    exit;
}

/**
 * Create a notification in the system
 */
function createNotification($db, $title, $message, $type = 'info') {
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (title, message, type, created_at)
            VALUES (:title, :message, :type, NOW())
        ");
        
        return $stmt->execute([
            ':title' => $title,
            ':message' => $message,
            ':type' => $type
        ]);
    } catch (PDOException $e) {
        error_log('Error creating notification: ' . $e->getMessage());
        return false;
    }
} 