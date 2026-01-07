<?php
/**
 * Authorized Users Configuration
 * 
 * Store all authorized user emails in this file.
 * This separates configuration from logic.
 * 
 * Usage in check_authorization.php:
 * $authorizedEmails = require_once 'config/authorized_users.php';
 */

return [
    // Admin users
    'heannasocute.22@gmail.com',
    
    // Regular users
    // 'user1@example.com',
    // 'user2@example.com',
    
    // Add more authorized emails below
];
?>