<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order details — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">YOUR ATER ACCOUNT</p>
            <h1>Order details.</h1><a class="text-link" href="account.php">← Back to your account</a>
        </div>
        <section id="order-detail">
            <div class="loading-state">Loading order details…</div>
        </section>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>