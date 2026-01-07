<?php
/**
 * Firebase User Authorization Check
 * 
 * Verifies if a user's email is authorized to access the system.
 * Called via AJAX from the client-side Firebase auth.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode([
        'error' => 'Method not allowed',
        'authorized' => false
    ]));
}

/**
 * AUTHORIZED EMAILS LIST
 * Add all authorized user emails here
 * TODO: Move to config file or database for better management
 */
$authorizedEmails = [
    'heannasocute.22@gmail.com',
    // Add more authorized emails below:
    'admin@example.com',
    'user@example.com',
];

try {
    // Get the email from POST request
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        exit(json_encode([
            'error' => 'Invalid JSON: ' . json_last_error_msg(),
            'authorized' => false
        ]));
    }
    
    $email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
    
    // Validate email is provided
    if (empty($email)) {
        http_response_code(400);
        exit(json_encode([
            'error' => 'Email is required',
            'authorized' => false
        ]));
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit(json_encode([
            'error' => 'Invalid email format',
            'authorized' => false
        ]));
    }
    
    // Check if email is in authorized list
    $isAuthorized = in_array($email, $authorizedEmails);
    
    // Log authorization check (sanitize email for privacy)
    $emailParts = explode('@', $email);
    $logEmail = substr($emailParts[0], 0, 3) . '***@' . $emailParts[1];
    error_log("Authorization check: {$logEmail} - " . ($isAuthorized ? 'ALLOWED' : 'DENIED'));
    
    // Return response
    http_response_code($isAuthorized ? 200 : 403);
    echo json_encode([
        'authorized' => $isAuthorized,
        'message' => $isAuthorized ? 'Access granted' : 'Access denied',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Authorization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'authorized' => false,
        'message' => 'An error occurred while checking authorization'
    ]);
}
?>