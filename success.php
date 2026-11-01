<?php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('You must be logged in to view this page.');
}

$userId = (int) $_SESSION['user_id'];
$sessionId = $_GET['session_id'] ?? '';

if (
    !is_string($sessionId) ||
    $sessionId === '' ||
    strlen($sessionId) > 255
) {
    http_response_code(400);
    exit('Invalid payment session.');
}
$sendConfirmation = false;

try {
    $stripeConfigPath = __DIR__ . '/config/stripe.php';
    if (!is_file($stripeConfigPath)) {
        $stripeConfigPath = __DIR__ . '/config/strip.php';
    }
    $stripeConfig = require $stripeConfigPath;

    \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    $sessionUserId = (string) ($session->metadata->user_id ?? '');
    $orderIdRaw = (string) ($session->metadata->order_id ?? '');

    if ($sessionUserId !== (string) $userId) {
        http_response_code(403);
        exit('Access denied.');
    }

    $orderId = filter_var(
        $orderIdRaw,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($orderId === false) {
        http_response_code(400);
        exit('This payment session is not linked to a valid order.');
    }

    if (
        $session->mode !== 'payment' ||
        $session->status !== 'complete' ||
        $session->payment_status !== 'paid'
    ) {
        http_response_code(400);
        exit('Payment has not been completed.');
    }

    $database = new Database();
    $pdo = $database->connect();

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id, total_amount, status,
                UNIX_TIMESTAMP(DATE_ADD(created_at, INTERVAL 6 HOUR)) AS cancel_until
         FROM orders
         WHERE id = ? AND user_id = ?
         FOR UPDATE"
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        exit('Order not found.');
    }

    $stmt = $pdo->prepare(
        "SELECT id, amount, payment_method, status, stripe_session_id
         FROM payments
         WHERE order_id = ?
         FOR UPDATE"
    );
    $stmt->execute([$orderId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($payments) !== 1) {
        throw new RuntimeException('Unexpected order payment records.');
    }

    $payment = $payments[0];

    $orderTotalCents = (int) round(
        (float) $order['total_amount'] * 100
    );

    $paymentTotalCents = (int) round(
        (float) $payment['amount'] * 100
    );

    if (
        $payment['payment_method'] !== 'visa' ||
        strtolower((string) $session->currency) !== 'usd' ||
        (int) $session->amount_total !== $orderTotalCents ||
        $paymentTotalCents !== $orderTotalCents
    ) {
        throw new RuntimeException('Payment amount or currency mismatch.');
    }

    if (
        $payment['stripe_session_id'] !== null &&
        $payment['stripe_session_id'] !== $session->id
    ) {
        throw new RuntimeException(
            'Order is already linked to another payment session.'
        );
    }

    // Reopening the same successful payment does not create another order.
    if ($payment['status'] === 'completed') {
        if ($payment['stripe_session_id'] !== $session->id) {
            throw new RuntimeException('Payment reference mismatch.');
        }
    } else {
        if (
            $order['status'] === 'cancelled' ||
            $payment['status'] !== 'pending'
        ) {
            $pdo->rollBack();
            http_response_code(409);
            exit(
                'Payment was received, but this order cannot be confirmed. '
                . 'Please contact the store with your payment reference.'
            );
        }

        $stmt = $pdo->prepare(
            "UPDATE payments
             SET status = 'completed',
                 stripe_session_id = ?
             WHERE id = ?"
        );
        $stmt->execute([$session->id, $payment['id']]);

        $confirmOrder = $pdo->prepare(
            "UPDATE orders SET status = 'confirmed' WHERE id = ? AND status = 'pending'"
        );
        $confirmOrder->execute([$orderId]);
        $sendConfirmation = true;
    }

    $pdo->commit();
    if ($sendConfirmation) {
    try {
        $stmt = $pdo->prepare(
            "SELECT email FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);

        $email = $stmt->fetchColumn();

        if (!is_string($email) || $email === '') {
            throw new RuntimeException('Customer email not found.');
        }

        require_once __DIR__ . '/classes/MailService.php';

        $mailService = new MailService();

        $sent = $mailService->sendOrderConfirmation(
            $email,
            $orderId,
            number_format($orderTotalCents / 100, 2, '.', '')
        );

        if (!$sent) {
            error_log(
                'Confirmation email was not sent for order ' . $orderId
            );
        }
    } catch (Throwable $e) {
        error_log(
            'Order confirmation failed for order '
            . $orderId . ': ' . $e->getMessage()
        );
    }
}
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Payment confirmation failed: ' . $e->getMessage());

    http_response_code(500);
    exit(
        'Unable to confirm payment right now. '
        . 'If you were charged, please contact the store '
        . 'before attempting another payment.'
    );
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment received — VELORA</title><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions"><script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head><body><?php $storeActive=''; include __DIR__.'/includes/store-header.php'; ?><main class="page-shell"><section class="success-panel"><div class="success-mark">✳</div><p class="eyebrow">A LOVELY CHOICE</p><h1>Payment received.</h1><p>Thank you for your order <strong>#<?= (int) $orderId ?></strong>. We’re preparing your fragrance with care.</p><a class="button button-dark" href="account.php">VIEW MY ORDERS</a><p><a class="text-link" href="index.php">Back to the store ↗</a></p></section></main><?php include __DIR__.'/includes/store-footer.php'; ?></body></html>
