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
$otp = $data['otp'] ?? '';

if (!is_string($email) || !is_string($otp)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid field types'
    ]);
}

$email = trim($email);
$otp = trim($otp);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $otp)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid email or OTP'
    ]);
}
$user = $userModel->findByEmail($email);

if ($user === null) {
    Response::json(400, [
        'success' => false,
        'message' => 'Invalid email or OTP'
    ]);
}
try {
    $isVerified = $emailVerification->verifyOtp((int) $user['id'], $otp);
} catch (Throwable $e) {
    error_log('OTP verification failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Unable to verify email. Please try again later.'
    ]);
}

if (!$isVerified) {
    Response::json(400, [
        'success' => false,
        'message' => 'Invalid or expired OTP'
    ]);
}

Response::json(200, [
    'success' => true,
    'message' => 'Email verified successfully'
]);
