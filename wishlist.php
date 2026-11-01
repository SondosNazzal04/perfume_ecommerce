<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Your wishlist — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">SAVED FOR LATER</p>
            <h1>Your wishlist.</h1>
            <p>The scents you’re coming back to.</p>
        </div>
        <div id="wishlist-items" class="product-grid">
            <div class="loading-state">Loading your saved fragrances…</div>
        </div>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>