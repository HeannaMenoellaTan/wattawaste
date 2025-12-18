<?php
session_start();
require_once('firebase_config.php');

header('Content-Type: application/json');

try {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['uid'])) {
        throw new Exception('Invalid request data');
    }
    
    $uid = $data['uid'];
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $displayName = $data['displayName'] ?? null;
    
    // Get Firebase database instance
    $database = getDatabase();
    
    // Look up user in Firebase Realtime Database
    $usersRef = $database->getReference('users');
    
    // Try to find user by UID first
    $userSnapshot = $usersRef->getChild($uid)->getSnapshot();
    
    if ($userSnapshot->exists()) {
        $userData = $userSnapshot->getValue();
        
        // Check if user is active
        if (isset($userData['Status']) && $userData['Status'] !== 'Active') {
            echo json_encode([
                'success' => false,
                'message' => 'Account is not active. Please contact administrator.'
            ]);
            exit;
        }
        
        // Store user data in session
        $_SESSION['user_id'] = $uid;
        $_SESSION['username'] = $userData['Username'] ?? ($displayName ?: ($email ?: $phone));
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        $_SESSION['role'] = $userData['Role'] ?? 'User';
        $_SESSION['logged_in'] = true;
        
        // Determine redirect based on role
        $redirect = ($userData['Role'] ?? 'User') === 'Admin' 
            ? 'admin_dasboard.php' 
            : 'index.php';
        
        echo json_encode([
            'success' => true,
            'redirect' => $redirect,
            'user' => [
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        // User doesn't exist in database - create a default entry or reject
        // For now, we'll reject users not in the database
        echo json_encode([
            'success' => false,
            'message' => 'User not found. Please contact administrator to create your account.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Save session error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>