<?php
header('Content-Type: application/json');
require_once 'firebase_config.php';

$database = getDatabase();

// Get user data from POST request
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['userId'] ?? '';
$email = $data['email'] ?? '';
$phone = $data['phone'] ?? '';
$name = $data['name'] ?? '';

if (empty($userId)) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

// Define admin emails
$adminEmails = [
    'heannasocute.22@gmail.com',
    'admin@wattawaste.com',
];

// Check if user is admin
$isAdmin = in_array(strtolower($email), array_map('strtolower', $adminEmails));
$role = $isAdmin ? 'admin' : 'user';

try {
    // Check if user exists in database
    $userRef = $database->getReference('users/' . $userId);
    $existingUser = $userRef->getValue();
    
    if ($existingUser === null) {
        // User doesn't exist - create them
        $userData = [
            'userId' => $userId,
            'email' => $email ?: '',
            'phone' => $phone ?: '',
            'name' => $name ?: '',
            'role' => $role,
            'createdAt' => date('Y-m-d H:i:s'),
            'lastLogin' => date('Y-m-d H:i:s')
        ];
        
        $userRef->set($userData);
        echo json_encode([
            'success' => true, 
            'message' => 'User created', 
            'user' => $userData,
            'isAdmin' => $isAdmin
        ]);
    } else {
        // User exists - update last login and role (in case admin status changed)
        $userRef->update([
            'lastLogin' => date('Y-m-d H:i:s'),
            'role' => $role
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'User updated', 
            'user' => array_merge($existingUser, ['role' => $role]),
            'isAdmin' => $isAdmin
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>