<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order placed — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <section class="success-panel">
            <div class="success-mark">✳</div>
            <p class="eyebrow">A LOVELY CHOICE</p>
            <h1>Thank you.</h1>
            <p>Your order <strong id="success-order">is being prepared</strong> is in. We’ll be in touch to confirm delivery and payment on arrival.</p>
            <p class="product-price" id="success-total"></p>
            <div id="order-window-notice" class="order-window-notice hidden"><strong>You have 6 hours to cancel this order.</strong>
                <p>If you need to change your items, cancel this order and place a new one before the timer ends.</p><span class="order-countdown" data-order-countdown></span>
            </div><a class="button button-dark" href="account.php">VIEW MY ORDERS</a>
            <p><a class="text-link" href="products.php">Back to fragrances ↗</a></p>
        </section>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>