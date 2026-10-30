<?php
// File: success.php

session_start(); // Start session to get user_id

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/vendor/autoload.php';

$stripeConfig = require_once __DIR__ . '/config/stripe.php';
\Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

// Connect to Database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    die("Database error: " . $e->getMessage());
}

// Get Session ID from URL
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    die("Invalid request. No session ID provided.");
}

try {
    // 1. Retrieve the Stripe Session to verify payment
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    if ($session->payment_status !== 'paid') {
        die("Payment was not successful.");
    }

    // 2. Extract the metadata we passed during Stripe Checkout creation
    $metadata = $session->metadata;
    $userId = $metadata->user_id ?? 1; // Use 1 if not set for testing
    $paymentMethod = 'visa';
    
    $addressData = [
        'city' => $metadata->city,
        'area' => $metadata->area,
        'street' => $metadata->street,
        'building' => $metadata->building
    ];

    $financialData = [
        'subtotal' => (float)$metadata->subtotal,
        'discount_amount' => 0.00,
        'delivery_fee' => (float)$metadata->delivery_fee,
        'total_amount' => (float)$metadata->total_amount
    ];

    // 3. Get current Cart Items
    $cartModel = new Cart($pdo);
    $cartItems = $cartModel->getCartItems($userId);

    if (empty($cartItems)) {
        die("Cart is empty. Cannot create order.");
    }

    // 4. Create the Order in our database
    $orderModel = new Order($pdo);
    $orderId = $orderModel->createOrder($userId, $addressData, $financialData, $cartItems, $paymentMethod);

    // 5. Success! Redirect to My Orders (or show success message)
    // We will just show a simple success message for now.
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Payment Successful</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container mt-5 text-center'>
            <div class='card shadow-sm border-0 p-5 mx-auto' style='max-width: 500px;'>
                <h1 class='text-success mb-3'><i class='fas fa-check-circle'></i> Payment Successful!</h1>
                <p class='lead'>Thank you for your purchase. Your order #<strong>{$orderId}</strong> has been placed successfully.</p>
                <a href='index.php' class='btn btn-primary mt-3'>Return to Store</a>
            </div>
        </div>
    </body>
    </html>
    ";

} catch (Exception $e) {
    echo "<h3>Error creating order: " . $e->getMessage() . "</h3>";
}
?>