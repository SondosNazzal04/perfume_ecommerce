<?php $storeActive = '';
$googleReturn = isset($_GET['return']) && is_string($_GET['return']) ? $_GET['return'] : 'account.php'; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create an account — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <link rel="stylesheet" href="assets/css/auth.css">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <form id="register-form" class="form-card api-form" data-endpoint="actions/register.php">
            <p class="eyebrow">A PLACE FOR YOUR FAVORITES</p>
            <h1>Make yourself at home.</h1>
            <p>Create your account to save fragrances, follow your orders, and check out with ease.</p><a class="google-button" href="actions/google-login.php?return=<?= urlencode($googleReturn) ?>"><svg viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.73 7.18l7.73 6C44.45 38.05 47 31.95 47 24.55z" />
                    <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.2A23.8 23.8 0 0 0 0 24c0 3.88.93 7.55 2.56 10.78l7.97-6.19z" />
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.14 1.45-4.89 2.3-8.18 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                </svg><span>Sign up with Google</span></a>
            <div class="auth-divider"><span>OR</span></div>
            <div class="form-field"><label for="name">Your name</label><input id="name" name="name" autocomplete="name" required></div>
            <div class="form-field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="+962…"></div>
            <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required></div>
            <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><small style="color:var(--muted);font-size:9px">Use at least 8 characters.</small></div><button class="button button-dark" type="submit">CREATE ACCOUNT</button>
            <div class="form-message" role="status"></div>
            <p class="form-note">Already have an account? <a href="login.php?return=<?= urlencode($googleReturn) ?>">Sign in</a></p>
        </form>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>