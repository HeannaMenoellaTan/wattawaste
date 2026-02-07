<?php
/**
 * Fetch Firebase Authentication Users
 * This file retrieves all authenticated users from Firebase Auth
 */

header('Content-Type: application/json');
require_once 'firebase_admin_check.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

// Firebase Project Configuration
$PROJECT_ID = 'wattawaste-d3503';
$DATABASE_URL = 'https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app/';

// You'll need to get this from Firebase Console > Project Settings > Service Accounts
// For now, we'll use the Realtime Database approach instead
// since PHP cannot directly access Firebase Auth without service account

// ALTERNATIVE: Fetch from Realtime Database /users node
function fetchDatabaseUsers() {
    global $DATABASE_URL;
    
    $url = $DATABASE_URL . 'users.json';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $users = json_decode($response, true);
        return $users ? $users : [];
    }
    
    return [];
}

// Fetch users from database
$databaseUsers = fetchDatabaseUsers();

// Format users for display
$formattedUsers = [];
foreach ($databaseUsers as $uid => $userData) {
    // Skip if user data is invalid
    if (!is_array($userData)) {
        continue;
    }
    
    $formattedUsers[] = [
        'id' => $uid,
        'name' => $userData['name'] ?? $userData['Username'] ?? 'Unknown',
        'email' => $userData['email'] ?? $userData['Email'] ?? 'N/A',
        'role' => $userData['role'] ?? $userData['Role'] ?? 'User',
        'status' => $userData['status'] ?? $userData['Status'] ?? 'Active',
        'lastActive' => $userData['lastActive'] ?? $userData['lastLogin'] ?? 'Never',
        'provider' => $userData['provider'] ?? 'email',
        'photoURL' => $userData['photoURL'] ?? null,
        'createdAt' => $userData['createdAt'] ?? $userData['created_at'] ?? null
    ];
}

// Return formatted users
echo json_encode([
    'success' => true,
    'count' => count($formattedUsers),
    'users' => $formattedUsers
]);