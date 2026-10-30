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

if (!is_string($email)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid field type'
    ]);
}

$email = trim($email);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid email address'
    ]);
}
$user = $userModel->findByEmail($email);

if ($user === null) {
    Response::json(200, [
        'success' => true,
        'message' => 'If the email is eligible, a verification code will be sent.'
    ]);
}

if ($user['email_verified_at'] !== null) {
    Response::json(200, [
        'success' => true,
        'message' => 'If the email is eligible, a verification code will be sent.'
    ]);
}

$otp = $emailVerification->resendOtp((int) $user['id']);

if ($otp === false) {
    Response::json(429, [
        'success' => false,
        'message' => 'Cannot resend OTP right now. Please try again later.'
    ]);
}

$emailSent = $mailService->sendOtp($email, $otp);

if (!$emailSent) {
    Response::json(500, [
        'success' => false,
        'message' => 'Failed to send verification email'
    ]);
}

Response::json(200, [
    'success' => true,
    'message' => 'Verification code sent successfully'
]);