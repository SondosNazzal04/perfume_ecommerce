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

$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$phone = $data['phone'] ?? '';

if (
    !is_string($name) ||
    !is_string($email) ||
    !is_string($password) ||
    !is_string($phone)
) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid field types'
    ]);
}

$name = trim($name);
$email = trim($email);
$phone = trim($phone);

if ($name === '' || $email === '' || $password === '') {
    Response::json(422, [
        'success' => false,
        'message' => 'Name, email and password are required'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid email address'
    ]);
}

if (strlen($password) < 8 || strlen($password) > 72) {
    Response::json(422, [
        'success' => false,
        'message' => 'Password must be 8-72 characters'
    ]);
}

$existingUser = $userModel->findByEmail($email);

if ($existingUser !== null) {
    Response::json(409, [
        'success' => false,
        'message' => 'Email is already registered'
    ]);
}

$userId = $userModel->register(
    $name,
    $email,
    $password,
    $phone
);

$otp = $emailVerification->createOtp($userId);

if ($otp === false) {
    Response::json(500, [
        'success' => false,
        'message' => 'Failed to create verification code'
    ]);
}

$emailSent = $mailService->sendOtp($email, $otp);

if (!$emailSent) {
    Response::json(500, [
        'success' => false,
        'message' => 'Failed to send verification email. Please try resending the code.'
    ]);
}

Response::json(201, [
    'success' => true,
    'message' => 'Registration successful! Check your email for the verification code.'
]);