<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// List of authorized Gmail addresses
$authorizedEmails = [
    'heannasocute.22@gmail.com',
    // Add more authorized emails here
];

// Get the email from POST request
$data = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($data['email'] ?? ''));

$isAuthorized = in_array($email, $authorizedEmails);

// Log for debugging (remove in production)
error_log("Authorization check for: $email - Result: " . ($isAuthorized ? 'ALLOWED' : 'DENIED'));

echo json_encode([
    'authorized' => $isAuthorized,
    'email' => $email
]);
?>