<?php $storeActive=''; ?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Checkout — VELORA</title>
  <link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions"><link rel="stylesheet" href="assets/css/auth.css">
  <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>
<body>
<?php include __DIR__.'/includes/store-header.php'; ?>
<main class="page-shell checkout-shell">
  <div class="checkout-heading"><a class="checkout-back" href="cart.php">← Return to bag</a><p class="eyebrow">VELORA · SECURE CHECKOUT</p><h1>Complete your order</h1><p>Delivery within 48 hours across Jordan.</p></div>
  <div class="checkout-grid checkout-inspired">
    <div class="checkout-main">
      <section id="checkout-signin" class="checkout-signin hidden"><p class="eyebrow">YOUR BAG IS SAVED</p><h2>One last step.</h2><p>Sign in or create an account to add your delivery details and complete your order.</p><a class="button button-dark" href="login.php?return=checkout.php">SIGN IN TO CHECK OUT</a><a class="text-link" href="register.php?return=checkout.php">Create an account ↗</a><p class="form-note">Your bag will still be here when you return.</p></section>
      <form id="checkout-form" class="hidden">
        <section class="checkout-section"><div class="checkout-section-head"><h2>Contact</h2><a id="checkout-account-link" href="login.php?return=checkout.php">Sign in</a></div><div class="form-field"><label for="contact-email">Email or mobile phone number</label><input id="contact-email" type="text" autocomplete="email" placeholder="Email or mobile phone number" required></div><label class="checkout-check"><input type="checkbox" name="offers" checked><span>Email me with news and offers</span></label></section>
        <section class="checkout-section"><h2>Delivery</h2><div class="form-field"><label for="country">Country/Region</label><select id="country" aria-label="Country or region"><option selected>Jordan</option></select></div><div class="checkout-two-col"><div class="form-field"><label for="first-name">First name</label><input id="first-name" autocomplete="given-name" placeholder="First name" required></div><div class="form-field"><label for="last-name">Last name</label><input id="last-name" autocomplete="family-name" placeholder="Last name" required></div></div><div class="form-field"><label for="street">Address</label><input id="street" autocomplete="street-address" placeholder="Address" required></div><div class="checkout-two-col"><div class="form-field"><label for="city">City</label><input id="city" autocomplete="address-level2" placeholder="City" required></div><div class="form-field"><label for="postal-code">Postal code (optional)</label><input id="postal-code" autocomplete="postal-code" placeholder="Postal code (optional)"></div></div><div class="form-field"><label for="phone">Phone</label><input id="phone" type="tel" autocomplete="tel" placeholder="Phone" required></div><label class="checkout-check"><input type="checkbox" id="save-info"><span>Save this information for next time</span></label></section>
        <section class="checkout-section"><h2>Shipping method</h2><div class="checkout-choice-list"><label class="checkout-choice"><input type="radio" name="delivery_zone" value="amman" checked><span><strong>Amman</strong><small>Delivery within 48 hours</small></span><b>JOD 2.000</b></label><label class="checkout-choice"><input type="radio" name="delivery_zone" value="outside"><span><strong>Outside Amman</strong><small>Delivery within 48 hours</small></span><b>JOD 3.000</b></label></div></section>
        <section class="checkout-section"><h2>Payment</h2><p class="checkout-muted">All transactions are secure and encrypted.</p><div class="checkout-choice-list checkout-payments"><label class="checkout-choice"><input type="radio" name="payment_choice" value="visa" checked><span><strong>Credit card</strong></span><span class="checkout-brands" aria-label="Visa, Mastercard, American Express">VISA <b>●●</b> AMEX <i>+4</i></span></label><div class="checkout-card-note">You’ll enter your card details securely on our payment provider.</div><label class="checkout-choice"><input type="radio" name="payment_choice" value="cash"><span><strong>Cash on Delivery (COD)</strong></span></label><label class="checkout-choice checkout-last-choice"><input type="radio" name="payment_choice" value="cliq" disabled><span><strong>CliQ</strong><small>Coming soon</small></span></label></div></section>
        <button class="button button-dark checkout-submit" type="submit">CONTINUE TO PAYMENT</button><div class="form-message" role="status"></div><p class="form-note checkout-terms">By continuing, you agree to be contacted to confirm delivery.</p>
      </form>
    </div>
    <aside class="summary-card checkout-summary"><h2>Your order</h2><div class="summary-line"><span>Subtotal</span><span id="checkout-subtotal">—</span></div><div class="summary-line"><span>Shipping</span><span id="checkout-delivery">—</span></div><div class="summary-line total"><span>Total</span><span id="checkout-total">—</span></div><p class="checkout-summary-note">Your fragrance will be carefully packed and delivered within 48 hours.</p></aside>
  </div>
</main>
<?php include __DIR__.'/includes/store-footer.php'; ?>
</body></html>
