<?php

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    Response::json(400, [
        'success' => false,
        'message' => 'Invalid JSON body'
    ]);
}

$email = $data['email'] ?? '';

if (
    !is_string($email) ||
    !filter_var(trim($email), FILTER_VALIDATE_EMAIL)
) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid email'
    ]);
}

$email = trim($email);
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$emailKey = hash('sha256', 'forgot-email:' . strtolower($email));
$ipKey = hash('sha256', 'forgot-ip:' . $ip);

if (
    $rateLimiter->isBlocked($emailKey, 3) ||
    $rateLimiter->isBlocked($ipKey, 10)
) {
    Response::json(429, [
        'success' => false,
        'message' => 'Too many requests. Please try again later.'
    ]);
}

$rateLimiter->recordFailure($emailKey);
$rateLimiter->recordFailure($ipKey);

$user = $userModel->findByEmail($email);

$message = 'If this email is registered, a password reset link will be sent.';

if ($user !== null && $user['email_verified_at'] !== null) {

    $token = $passwordReset->createToken((int) $user['id']);

   $resetLink = 'http://localhost/perfume_ecommerce/actions/reset-password.php?token=' . urlencode($token);

    $sent = $mailService->sendPasswordReset($email, $resetLink);

    if (!$sent) {
        error_log('Password reset email delivery failed');
    }
}

Response::json(200, [
    'success' => true,
    'message' => $message
]);
