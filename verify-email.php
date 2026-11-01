<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify your email — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <form id="verify-form" class="form-card api-form" data-endpoint="actions/verify-otp.php">
            <p class="eyebrow">ONE QUICK STEP</p>
            <h1>Check your inbox.</h1>
            <p>Enter the six-digit code we sent to your email to finish creating your account.</p>
            <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required></div>
            <div class="form-field velora-otp-field"><label for="otp">Six-digit code</label><div class="velora-otp-slots" aria-hidden="true"><span class="velora-otp-slot">•</span><span class="velora-otp-slot">•</span><span class="velora-otp-slot">•</span><span class="velora-otp-slot">•</span><span class="velora-otp-slot">•</span><span class="velora-otp-slot">•</span></div><input class="velora-otp-capture" id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" aria-describedby="otp-help" required><p id="otp-help" class="velora-otp-help">Type or paste the six-digit code.</p></div><button class="button button-dark" type="submit">VERIFY EMAIL</button>
            <div class="form-message" role="status"></div>
            <p class="form-note">Didn’t get a code? <button type="button" id="resend-code" class="velora-resend-button">Send a new one</button></p>
        </form>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?><script defer src="assets/js/velora-otp-ui.js?v=1"></script><script>
        document.addEventListener('DOMContentLoaded', () => {
            const e = document.querySelector('#email');
            if (e) e.value = sessionStorage.getItem('ater_email') || '';
            document.querySelector('#resend-code')?.addEventListener('click', async () => {
                const f = document.querySelector('#verify-form'),
                    email = e.value;
                try {
                    const r = await fetch('actions/resend-otp.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                email
                            })
                        }),
                        d = await r.json();
                    const m = f.querySelector('.form-message');
                    m.textContent = d.message || 'A new code has been requested.';
                    m.classList.toggle('success', !!d.success);
                } catch {}
            });
        });
    </script>
</body>

</html>