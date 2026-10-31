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

$token = $data['token'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (!is_string($token) || !is_string($newPassword)) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid field types'
    ]);
}

if (
    !preg_match('/^[a-f0-9]{64}$/', $token) ||
    strlen($newPassword) < 8 ||
    strlen($newPassword) > 72
) {
    Response::json(422, [
        'success' => false,
        'message' => 'Invalid token or password. Password must be 8-72 characters.'
    ]);
}

try {
    $success = $passwordReset->resetPassword($token, $newPassword);
} catch (Throwable $e) {
    error_log('Password reset failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Unable to reset password. Please try again later.'
    ]);
}

if (!$success) {
    Response::json(400, [
        'success' => false,
        'message' => 'Invalid or expired reset token'
    ]);
}

Response::json(200, [
    'success' => true,
    'message' => 'Password reset successfully'
]);