<?php
/**
 * Set Session - Create PHP session from Firebase user data
 * Called by login.php after Firebase authentication
 */

session_start();
header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data'
    ]);
    exit();
}

try {
    // Set session variables
    $_SESSION['user_id'] = $data['user_id'] ?? '';
    $_SESSION['username'] = $data['username'] ?? '';
    $_SESSION['email'] = $data['email'] ?? '';
    $_SESSION['phone'] = $data['phone'] ?? '';
    $_SESSION['role'] = $data['role'] ?? 'User';
    $_SESSION['status'] = $data['status'] ?? 'Active';
    
    // Log successful session creation
    error_log("Session created for user: " . $_SESSION['username'] . " (Role: " . $_SESSION['role'] . ")");
    
    echo json_encode([
        'success' => true,
        'message' => 'Session created successfully',
        'role' => $_SESSION['role']
    ]);
    
} catch (Exception $e) {
    error_log("Session creation error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create session: ' . $e->getMessage()
    ]);
}
?>