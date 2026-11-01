<?php



require_once __DIR__ . '/../includes/init.php';

$returnPath = $_GET['return'] ?? 'account.php';
$allowedReturns = ['cart.php', 'checkout.php', 'wishlist.php', 'account.php', 'index.php'];
if (!in_array($returnPath, $allowedReturns, true) && !preg_match('/^product\.php\?id=\d+$/', (string) $returnPath)) {
    $returnPath = 'account.php';
}
$_SESSION['google_return'] = $returnPath;

try {
    $googleAuth = new GoogleAuth();
} catch (Throwable $e) {
    error_log('Google sign-in is unavailable: ' . $e->getMessage());
    header('Location: ../login.php?google=unavailable&return=' . urlencode($returnPath));
    exit;
}

header('Location: ' . $googleAuth->getLoginUrl());
exit;
