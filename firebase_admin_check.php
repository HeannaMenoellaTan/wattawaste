<?php
/**
 * Firebase Admin Role Verification
 * Helper functions to check user roles from Firebase
 */

require_once 'firebase_config.php';

/**
 * Check if a user is an Admin
 * @param string $userId - Firebase user ID or username
 * @return bool - True if user is admin, false otherwise
 */
function isAdmin($userId) {
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
                    if (isset($userData['Username']) && $userData['Username'] === $userId) {
                        $snapshot = $database->getReference("users/$key")->getSnapshot();
                        break;
                    }
                }
            }
        }
        
        if ($snapshot->exists()) {
            $userData = $snapshot->getValue();
            return isset($userData['Role']) && $userData['Role'] === 'Admin';
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Admin check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user data from Firebase
 * @param string $userId - Firebase user ID or username
 * @return array|null - User data or null if not found
 */
function getUserData($userId) {
    try {
        $database = getDatabase();
        
        // Try to get user by direct ID
        $userRef = $database->getReference("users/$userId");
        $snapshot = $userRef->getSnapshot();
        
        // If not found by ID, search by username
        if (!$snapshot->exists()) {
            $usersRef = $database->getReference("users");
            $allUsers = $usersRef->getValue();
            
            if ($allUsers) {
                foreach ($allUsers as $key => $userData) {
                    if (isset($userData['Username']) && $userData['Username'] === $userId) {
                        return $userData;
                    }
                }
            }
        }
        
        if ($snapshot->exists()) {
            return $snapshot->getValue();
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Get user data error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user is active
 * @param string $userId - Firebase user ID or username
 * @return bool - True if user is active, false otherwise
 */
function isUserActive($userId) {
    try {
        $userData = getUserData($userId);
        return $userData && isset($userData['Status']) && $userData['Status'] === 'Active';
    } catch (Exception $e) {
        error_log("User active check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user role
 * @param string $userId - Firebase user ID or username
 * @return string|null - User role or null if not found
 */
function getUserRole($userId) {
    try {
        $userData = getUserData($userId);
        return $userData && isset($userData['Role']) ? $userData['Role'] : null;
    } catch (Exception $e) {
        error_log("Get user role error: " . $e->getMessage());
        return null;
    }
}

/**
 * Verify admin access and redirect if unauthorized
 * @param string $redirectUrl - Where to redirect non-admins (default: login.php)
 */
function requireAdmin($redirectUrl = 'login.php') {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
        header("Location: $redirectUrl");
        exit();
    }
    
    // Get user identifier
    $userId = $_SESSION['username'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        header("Location: $redirectUrl");
        exit();
    }
    
    // Check if user is admin
    if (!isAdmin($userId)) {
        // Not an admin - redirect to regular dashboard
        header("Location: index.php");
        exit();
    }
    
    // Check if user is active
    if (!isUserActive($userId)) {
        session_destroy();
        header("Location: login.php?error=inactive");
        exit();
    }
}

/**
 * Set user session data from Firebase
 * @param string $userId - Firebase user ID or username
 * @return bool - True if successful, false otherwise
 */
function setUserSession($userId) {
    try {
        $userData = getUserData($userId);
        
        if ($userData) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $userData['Username'] ?? $userId;
            $_SESSION['email'] = $userData['Email'] ?? '';
            $_SESSION['phone'] = $userData['Phone'] ?? '';
            $_SESSION['role'] = $userData['Role'] ?? 'User';
            $_SESSION['status'] = $userData['Status'] ?? 'Active';
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Set user session error: " . $e->getMessage());
        return false;
    }
}
?>