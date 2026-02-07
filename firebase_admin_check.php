<?php
/**
 * Firebase Admin Check
 * Verifies admin access using Firebase database
 */

require_once __DIR__ . '/firebase_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is an Admin from Firebase
 * @param string $userId - Firebase user ID or username
 * @return bool - True if user is admin, false otherwise
 */
if (!function_exists('isAdminUser')) {
    function isAdminUser($userId) {
        try {
            $database = getDatabase();
            
            // First, try to get user by direct ID
            $userRef = $database->getReference("users/$userId");
            $snapshot = $userRef->getSnapshot();
            
            // If not found by ID, search by username
            if (!$snapshot->exists()) {
                $usersRef = $database->getReference("users");
                $allUsers = $usersRef->getValue();
                
                if ($allUsers) {
                    foreach ($allUsers as $key => $userData) {
                        if (isset($userData['username']) && $userData['username'] === $userId) {
                            $snapshot = $database->getReference("users/$key")->getSnapshot();
                            break;
                        }
                    }
                }
            }
            
            if ($snapshot->exists()) {
                $userData = $snapshot->getValue();
                return isset($userData['role']) && strtolower($userData['role']) === 'admin';
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Admin check error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Check if user is logged in
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) || isset($_SESSION['username']);
    }
}

/**
 * Check if user is admin (from session)
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
    }
}

/**
 * Get current username
 */
if (!function_exists('getCurrentUsername')) {
    function getCurrentUsername() {
        return $_SESSION['username'] ?? 'Administrator';
    }
}

/**
 * Get current user email
 */
if (!function_exists('getCurrentUserEmail')) {
    function getCurrentUserEmail() {
        return $_SESSION['email'] ?? '';
    }
}

/**
 * Get current user ID
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        return $_SESSION['user_id'] ?? $_SESSION['username'] ?? null;
    }
}

/**
 * Get current user role
 */
if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole() {
        return $_SESSION['role'] ?? 'user';
    }
}

/**
 * Require admin role - redirect if not admin
 */
if (!function_exists('requireAdmin')) {
    function requireAdmin($redirectUrl = 'login.php') {
        // FOR DEVELOPMENT ONLY - Auto-create admin session
        // REMOVE THIS BLOCK IN PRODUCTION!
        if (!isLoggedIn()) {
            $_SESSION['user_id'] = 'admin_001';
            $_SESSION['username'] = 'Administrator';
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = 'admin@wattawaste.com';
            $_SESSION['login_time'] = time();
            error_log("⚠️ DEV MODE: Auto-created admin session");
        }
        
        // PRODUCTION CODE - Uncomment these lines in production:
        /*
        if (!isLoggedIn()) {
            header("Location: $redirectUrl");
            exit();
        }
        
        $userId = getCurrentUserId();
        if (!isAdminUser($userId)) {
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            header("Location: index.php");
            exit();
        }
        */
    }
}

/**
 * Require login - redirect if not logged in
 */
if (!function_exists('requireLogin')) {
    function requireLogin($redirectUrl = 'login.php') {
        if (!isLoggedIn()) {
            header("Location: $redirectUrl");
            exit();
        }
    }
}

/**
 * Log activity
 */
if (!function_exists('logActivity')) {
    function logActivity($action, $details = '') {
        $username = getCurrentUsername();
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] Admin: $username | Action: $action | Details: $details");
    }
}

/**
 * Set session message
 */
if (!function_exists('setMessage')) {
    function setMessage($type, $message) {
        $_SESSION['message_type'] = $type;
        $_SESSION['message'] = $message;
    }
}

/**
 * Get and clear session message
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
 * Check if current user is admin (alias for isAdmin)
 * Used by sidebar.php for backward compatibility
 */
if (!function_exists('isCurrentUserAdmin')) {
    function isCurrentUserAdmin() {
        return isAdmin();
    }
}
?>