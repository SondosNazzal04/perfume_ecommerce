
<?php

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/GuestCart.php';

$googleAuth = new GoogleAuth();

$state = $_GET['state'] ?? '';
$savedState = $_SESSION['google_oauth_state'] ?? '';

unset($_SESSION['google_oauth_state']);

// Validate OAuth state to prevent CSRF
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

// Get Google user information
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
$user = $userModel->findByGoogleId(
    $googleUser['google_id']
);

// 2. If Google account is not linked
if ($user === null) {

    // Search by email
    $user = $userModel->findByEmail(
        $googleUser['email']
    );

    if ($user !== null) {

        // Existing account must already be verified
        if ($user['email_verified_at'] === null) {
            Response::json(403, [
                'success' => false,
                'message' => 'Please verify your email first'
            ]);
        }

        // Link the existing account with Google
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

        // Create a new Google user
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

// Start the authenticated user session
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_role'] = $user['role'];

// Merge guest cart into the authenticated user's cart
try {
    GuestCart::mergeInto(
        $conn,
        (int) $user['id']
    );
} catch (Throwable $e) {
    error_log(
        'Guest cart merge after Google sign-in failed: '
        . $e->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| GOOGLE LOGIN REDIRECT FIX
|--------------------------------------------------------------------------
| The website runs directly at:
| http://localhost:3000/
|
| Redirect to a root-relative page instead of calculating
| a project folder from SCRIPT_NAME.
|--------------------------------------------------------------------------
*/

// Get the requested return page
$returnPath = $_SESSION['google_return'] ?? 'account.php';

// Remove the one-time return destination from the session
unset($_SESSION['google_return']);

// Only allow known pages
$allowedReturns = [
    'cart.php',
    'checkout.php',
    'wishlist.php',
    'account.php',
    'index.php'
];

// Also allow a product details page with a numeric ID
$isProductPage = (
    is_string($returnPath) &&
    preg_match('/^product\.php\?id=\d+$/D', $returnPath) === 1
);

// Reject invalid or unexpected return paths
if (
    !is_string($returnPath) ||
    (
        !in_array($returnPath, $allowedReturns, true) &&
        !$isProductPage
    )
) {
    $returnPath = 'account.php';
}

// Redirect to the website root, not the /actions/ folder
header('Location: /' . $returnPath, true, 302);
exit;
