<?php

require_once __DIR__ . '/../includes/init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$state = $_GET['state'] ?? '';
$savedState = $_SESSION['google_oauth_state'] ?? '';

unset($_SESSION['google_oauth_state']);

if (
    !is_string($state) ||
    !is_string($savedState) ||
    $savedState === '' ||
    !hash_equals($savedState, $state)
) {
    Response::json(403, [
        'message' => 'Invalid Google OAuth state'
    ]);
}

if (isset($_GET['error'])) {
    Response::json(400, [
        'message' => 'Google login was cancelled or denied'
    ]);
}

$code = $_GET['code'] ?? '';

if (!is_string($code) || $code === '') {
    Response::json(400, [
        'message' => 'Authorization code is missing'
    ]);
}
try {
    $googleUser = $googleAuth->getGoogleUser($code);
} catch (Throwable $e) {
    error_log('Google OAuth failed: ' . $e->getMessage());
    $googleUser = null;
}

if ($googleUser === null) {
    Response::json(401, [
        'success' => false,
        'message' => 'Google authentication failed'
    ]);
}
// 1. Search by Google ID
$user = $userModel->findByGoogleId($googleUser['google_id']);

// 2. If Google account is not linked
if ($user === null) {

    // Search by email
    $user = $userModel->findByEmail($googleUser['email']);

    if ($user !== null) {

        // Existing account must already be verified
        if ($user['email_verified_at'] === null) {
            Response::json(403, [
                'success' => false,
                'message' => 'Please verify your email first'
            ]);
        }

        // Link Google account
        $linked = $userModel->linkGoogleAccount(
            (int) $user['id'],
            $googleUser['google_id']
        );

        if (!$linked) {
            Response::json(409, [
                'success' => false,
                'message' => 'Unable to link Google account'
            ]);
        }
    } else {

        // Create new Google user
        $userId = $userModel->createGoogleUser(
            $googleUser['name'],
            $googleUser['email'],
            $googleUser['google_id']
        );

        $user = $userModel->findById($userId);
    }
}

// Check if user was retrieved successfully
if ($user === null) {
    Response::json(500, [
        'success' => false,
        'message' => 'Unable to retrieve user'
    ]);
}

// Start user session
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_role'] = $user['role'];

// Redirect to homepage
header('Location: /perfume_ecommerce/index.php');
exit;