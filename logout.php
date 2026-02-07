<?php
/**
 * Logout Script
 * Handles user logout and session cleanup
 */

// Start session
session_start();

// Store username for logging
$username = $_SESSION['username'] ?? 'Unknown';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Log the logout
error_log("[" . date('Y-m-d H:i:s') . "] User logged out: $username");

// Redirect to login page
header("Location: login.php");
exit();
?>