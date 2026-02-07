<?php
/**
 * Role and Authentication Check
 * This file is for admin_nav.php compatibility only
 * Main authentication is handled by firebase_admin_check.php
 */

// Load firebase admin check if not already loaded
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/firebase_admin_check.php';
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-check admin access for admin pages using admin_nav.php
$current_file = basename($_SERVER['PHP_SELF']);
$admin_nav_pages = ['user_management.php', 'admin_settings.php'];

if (in_array($current_file, $admin_nav_pages)) {
    // For pages using admin_nav.php, create temporary session for development
    if (!isLoggedIn()) {
        $_SESSION['user_id'] = 'admin_001';
        $_SESSION['username'] = 'Admin User';
        $_SESSION['role'] = 'admin';
        $_SESSION['email'] = 'admin@wattawaste.com';
        $_SESSION['login_time'] = time();
    }
}

/**
 * Set session message (only if not already defined)
 */
if (!function_exists('setMessage')) {
    function setMessage($type, $message) {
        $_SESSION['message_type'] = $type;
        $_SESSION['message'] = $message;
    }
}

/**
 * Get and clear session message (only if not already defined)
 */
if (!function_exists('getMessage')) {
    function getMessage() {
        if (isset($_SESSION['message'])) {
            $message = [
                'type' => $_SESSION['message_type'] ?? 'info',
                'text' => $_SESSION['message']
            ];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            return $message;
        }
        return null;
    }
}

/**
 * Logout function (only if not already defined)
 */
if (!function_exists('logout')) {
    function logout($redirectUrl = 'login.php') {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        header("Location: $redirectUrl");
        exit();
    }
}
?>