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
$password = $data['password'] ?? '';

if (!is_string($email) || !is_string($password)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid field types'
    ]);
}

$email = trim($email);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid email or password'
    ]);
}
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$emailKey = hash('sha256', 'email:' . strtolower($email));
$ipKey = hash('sha256', 'ip:' . $ip);

if (
    $rateLimiter->isBlocked($emailKey) ||
    $rateLimiter->isBlocked($ipKey, 20)
) {
    Response::json(429, [
        'success' => false,
        'message' => 'Too many login attempts. Please try again later.'
    ]);
}


$user = $userModel->login($email, $password);

if ($user === null) {

    $rateLimiter->recordFailure($emailKey);
    $rateLimiter->recordFailure($ipKey);

    Response::json(401, [
        'success' => false,
        'message' => 'Invalid credentials or email not verified'
    ]);
}

$rateLimiter->clearAttempts($emailKey);
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];

Response::json(200, [
    'success' => true,
    'message' => 'Login successful',
    'user' => $user
]);
