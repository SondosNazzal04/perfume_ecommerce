<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Your bag — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">A GOOD CHOICE</p>
            <h1>Your bag.</h1>
            <p>A little something lovely, chosen by you.</p>
        </div>
        <div id="cart-empty" class="empty-state hidden">Your bag is empty. <a class="text-link" href="products.php">Find a fragrance ↗</a></div>
        <div id="cart-content" class="cart-layout hidden">
            <div id="cart-items"></div>
            <aside class="summary-card">
                <h2>Order summary</h2>
                <form id="coupon-form" class="coupon-form"><label class="sr-only" for="coupon-code">Coupon code</label><input id="coupon-code" name="code" type="text" maxlength="50" placeholder="Coupon code" autocomplete="off"><button type="submit">Apply</button></form>
                <p id="coupon-message" class="coupon-message" role="status"></p>
                <div class="summary-line"><span>Subtotal</span><span id="cart-subtotal">0.000 JOD</span></div>
                <div class="summary-line coupon-discount hidden" id="coupon-discount-row"><span>Coupon discount <strong id="coupon-applied-code"></strong></span><span id="cart-discount">−0.000 JOD</span></div>
                <div class="summary-line"><span>Delivery</span><span id="cart-delivery">5.000 JOD</span></div>
                <div class="summary-line total"><span>Total</span><span id="cart-total">0.000 JOD</span></div>
                <p style="font-size:10px;color:var(--muted);line-height:1.7">Complimentary delivery on orders over 100 JOD.</p><a class="button button-dark" href="checkout.php">Continue to checkout ↗</a><a class="text-link" style="display:block;margin:18px auto 0" href="products.php">Continue shopping</a>
            </aside>
        </div>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>