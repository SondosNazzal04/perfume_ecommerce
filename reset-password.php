<?php $storeActive = '';
$resetToken = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Choose a new password — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <form id="reset-form" class="form-card api-form" data-endpoint="actions/reset-password.php">
            <p class="eyebrow">A FRESH START</p>
            <h1>Choose a new password.</h1>
            <p>Set a new password to get back to your Ater account.</p><input type="hidden" name="token" value="<?= htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div><button class="button button-dark" type="submit">UPDATE PASSWORD</button>
            <div class="form-message" role="status"></div>
        </form>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>