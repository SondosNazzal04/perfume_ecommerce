<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My account — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">YOUR VELORA ACCOUNT</p>
            <h1 id="account-name">Welcome.</h1>
            <p id="account-email">A considered fragrance collection, just for you.</p>
        </div>
        <div id="account-content" class="account-layout">
            <nav class="account-menu"><a href="#orders">Order history</a><a href="wishlist.php">Wishlist</a><a href="products.php">Continue shopping</a><button id="logout-button" type="button">Sign out</button></nav>
            <section id="orders">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">A LITTLE HISTORY</p>
                        <h2>My orders</h2>
                    </div>
                </div>
                <div id="orders-list">
                    <div class="loading-state">Loading your orders…</div>
                </div>
            </section>
        </div>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>