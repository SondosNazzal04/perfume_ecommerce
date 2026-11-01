<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset your password — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <form class="form-card api-form" data-endpoint="actions/forgot-password.php">
            <p class="eyebrow">IT HAPPENS</p>
            <h1>Let’s get you back in.</h1>
            <p>Share the email address linked to your account. If it’s registered, we’ll send a reset link.</p>
            <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required></div><button class="button button-dark" type="submit">SEND RESET LINK</button>
            <div class="form-message" role="status"></div>
            <p class="form-note"><a href="login.php">Back to sign in</a></p>
        </form>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>