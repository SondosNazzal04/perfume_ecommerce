<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../core/Response.php';

if (!isset($_SESSION['user_id'])) {
    Response::json(401, [
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['POST', 'GET', 'PUT'], true)) {
    header('Allow: POST, GET, PUT');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$id = isset($_GET['id'])
    ? filter_var(
        $_GET['id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : null;

if ($id === false) {
    Response::json(400, [
        'success' => false,
        'message' => 'Order ID must be a positive integer'
    ]);
}

function getJsonBody(): array
{
    try {
        $data = json_decode(
            file_get_contents('php://input'),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        Response::json(400, [
            'success' => false,
            'message' => 'Invalid JSON body'
        ]);
    }

    if (!$data instanceof stdClass) {
        Response::json(400, [
            'success' => false,
            'message' => 'JSON body must be an object'
        ]);
    }

    return (array) $data;
}

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Orders connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $orderModel = new Order($pdo);

    switch ($method) {
        case 'POST':
            $body = getJsonBody();

            $addressData = [];

            $addressLimits = [
                'city' => 100,
                'area' => 100,
                'street' => 150,
                'building' => 50
            ];

            foreach ($addressLimits as $field => $maxLength) {
                $value = $body[$field] ?? '';

                if (!is_string($value)) {
                    Response::json(422, [
                        'success' => false,
                        'message' => $field . ' must be a string'
                    ]);
                }

                $value = trim($value);

                if ($field !== 'building' && $value === '') {
                    Response::json(422, [
                        'success' => false,
                        'message' => $field . ' is required'
                    ]);
                }

                if (mb_strlen($value, 'UTF-8') > $maxLength) {
                    Response::json(422, [
                        'success' => false,
                        'message' => $field
                            . ' must not exceed '
                            . $maxLength
                            . ' characters'
                    ]);
                }

                $addressData[$field] = $value;
            }

            $paymentMethod = $body['payment_method'] ?? 'cash';

            if (!is_string($paymentMethod)) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Payment method must be a string'
                ]);
            }

            $paymentMethod = trim($paymentMethod);

            if (!in_array($paymentMethod, ['cash', 'visa'], true)) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Payment method must be cash or visa'
                ]);
            }

            $cartModel = new Cart($pdo);
            $cartItems = $cartModel->getCartItems($userId);

            if (empty($cartItems)) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Your cart is empty.'
                ]);
            }

            // Calculate in cents to avoid truncating monetary values.
            $subtotalCents = 0;

            foreach ($cartItems as $item) {
                $unitPriceCents = (int) round(
                    (float) $item['price'] * 100
                );

                $subtotalCents += $unitPriceCents * (int) $item['quantity'];
            }

            $deliveryFeeCents = $subtotalCents >= 10000 ? 0 : 500;
            $totalCents = $subtotalCents + $deliveryFeeCents;

            if ($totalCents > 9999999999) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Order total exceeds the supported limit'
                ]);
            }

            $subtotal = $subtotalCents / 100;
            $deliveryFee = $deliveryFeeCents / 100;
            $totalAmount = $totalCents / 100;

            $financialData = [
                'subtotal' => $subtotal,
                'discount_amount' => 0.00,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount
            ];

            if ($paymentMethod === 'visa') {
                require_once __DIR__ . '/../vendor/autoload.php';

                $stripeConfig = require __DIR__ . '/../config/stripe.php';

                \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

                $lineItems = [];

                foreach ($cartItems as $item) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $item['product_name']
                                    . ' (' . $item['size_ml'] . 'ml)'
                            ],
                            'unit_amount' => (int) round(
                                (float) $item['price'] * 100
                            )
                        ],
                        'quantity' => (int) $item['quantity']
                    ];
                }

                if ($deliveryFeeCents > 0) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Delivery Fee'
                            ],
                            'unit_amount' => $deliveryFeeCents
                        ],
                        'quantity' => 1
                    ];
                }

                $orderId = $orderModel->createOrder(
                    $userId,
                    $addressData,
                    $financialData,
                    $cartItems,
                    'visa'
                );

                try {
                    $session = \Stripe\Checkout\Session::create([
                        'line_items' => $lineItems,
                        'mode' => 'payment',
                        'success_url' =>
                        'http://localhost/perfume_ecommerce/success.php'
                            . '?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' =>
                        'http://localhost/perfume_ecommerce/checkout.php',
                        'metadata' => [
                            'user_id' => (string) $userId,
                            'order_id' => (string) $orderId
                        ]
                    ], [
                        'idempotency_key' => 'checkout-order-' . $orderId
                    ]);
                } catch (
                    \Stripe\Exception\InvalidRequestException |
                    \Stripe\Exception\AuthenticationException |
                    \Stripe\Exception\PermissionException $e
                ) {
                    error_log(
                        'Stripe rejected checkout for order '
                            . $orderId . ': ' . $e->getMessage()
                    );

                    // Stripe rejected session creation: release reserved stock.
                    try {
                        $cancelled = $orderModel->cancelOrder($orderId, false);

                        if (!$cancelled) {
                            throw new RuntimeException(
                                'Unable to cancel the unpaid order.'
                            );
                        }
                    } catch (Throwable $cancelError) {
                        error_log(
                            'Failed to release order '
                                . $orderId . ': ' . $cancelError->getMessage()
                        );

                        Response::json(500, [
                            'success' => false,
                            'order_id' => $orderId,
                            'message' =>
                            'Checkout failed. Please contact the store '
                                . 'to release this unpaid order.'
                        ]);
                    }

                    Response::json(502, [
                        'success' => false,
                        'order_id' => $orderId,
                        'message' =>
                        'Unable to start card payment. '
                            . 'The order was cancelled and stock was released.'
                    ]);
                } catch (Throwable $e) {
                    // A connection failure does not prove session creation failed.
                    error_log(
                        'Stripe checkout result uncertain for order '
                            . $orderId . ': ' . $e->getMessage()
                    );

                    Response::json(503, [
                        'success' => false,
                        'order_id' => $orderId,
                        'message' =>
                        'Payment session status is uncertain. '
                            . 'Please contact the store before starting another order.'
                    ]);
                }

                try {
                    $stmt = $pdo->prepare(
                        "UPDATE payments
             SET stripe_session_id = ?
             WHERE order_id = ?
               AND payment_method = 'visa'
               AND status = 'pending'
               AND stripe_session_id IS NULL"
                    );

                    $stmt->execute([$session->id, $orderId]);

                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Unable to link the Stripe session to the payment.'
                        );
                    }
                } catch (Throwable $e) {
                    error_log(
                        'Stripe session linking failed for order '
                            . $orderId . ', session '
                            . $session->id . ': ' . $e->getMessage()
                    );

                    // Keep the order pending; an actual Stripe session exists.
                    Response::json(500, [
                        'success' => false,
                        'order_id' => $orderId,
                        'message' =>
                        'Unable to finish checkout setup. '
                            . 'Please contact the store before starting another order.'
                    ]);
                }

                Response::json(200, [
                    'success' => true,
                    'message' => 'Redirecting to Stripe...',
                    'order_id' => $orderId,
                    'checkout_url' => $session->url
                ]);
            }

            // Cash orders are created once, here.
            try {
                $orderId = $orderModel->createOrder(
                    $userId,
                    $addressData,
                    $financialData,
                    $cartItems,
                    'cash'
                );
            } catch (PDOException $e) {
                throw $e;
            } catch (Exception $e) {
                error_log('Cash order creation failed: ' . $e->getMessage());

                Response::json(400, [
                    'success' => false,
                    'message' => 'Unable to create order. Please check your cart and stock availability.'
                ]);
            }

            Response::json(201, [
                'success' => true,
                'message' => 'Order created successfully.',
                'order_id' => $orderId,
                'calculated_total' => $totalAmount
            ]);

        case 'GET':
            if ($id !== null) {
                $orderData = $orderModel->getCustomerOrderDetails(
                    $userId,
                    $id
                );

                if (!$orderData) {
                    Response::json(404, [
                        'success' => false,
                        'message' => 'Order not found'
                    ]);
                }

                Response::json(200, [
                    'success' => true,
                    'data' => $orderData
                ]);
            }

            $orders = $orderModel->getOrdersByUser($userId);

            Response::json(200, [
                'success' => true,
                'count' => count($orders),
                'data' => $orders
            ]);

        case 'PUT':
            if ($id === null) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Order ID is required'
                ]);
            }

            $order = $orderModel->getCustomerOrderDetails($userId, $id);

            if (!$order) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }

            try {
                $cancelled = $orderModel->cancelOrder($id, false);
            } catch (PDOException $e) {
                throw $e;
            } catch (Exception $e) {
                error_log('Order cancellation rejected: ' . $e->getMessage());

                Response::json(400, [
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this stage'
                ]);
            }

            if (!$cancelled) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Failed to cancel order'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Order cancelled successfully. Stock has been returned.'
            ]);
    }
} catch (Throwable $e) {
    error_log('Orders API failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Unable to process the order request'
    ]);
}
