<?php
// File: api/orders.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");


// Check Authentication BEFORE processing any order requests
session_start();
if (!isset($_SESSION['user_id'])) {
    Response::json(401, ["success" => false, "message" => "Unauthorized. Please login."]);
    exit;
}
// Get the real user ID from the session
$userId = $_SESSION['user_id']; 

//Connect to Database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

// Temporary user ID for testing
$userId = 1;
$method = $_SERVER["REQUEST_METHOD"];
$id = isset($_GET["id"]) ? filter_var($_GET["id"], FILTER_VALIDATE_INT) : null;

// Helper function to read JSON body
function getJsonBody(): array {
    $rawBody = file_get_contents("php://input");
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, ["success" => false, "message" => "Invalid JSON body"]);
    }
    return is_array($data) ? $data : [];
}

try {
    $orderModel = new Order($pdo);

    switch ($method) {
    
        // CREATE ORDER (CHECKOUT)
        case "POST":
            $body = getJsonBody();

            // 1. Validate Address
            $addressData = [
                'city' => trim($body["city"] ?? ""),
                'area' => trim($body["area"] ?? ""),
                'street' => trim($body["street"] ?? ""),
                'building' => trim($body["building"] ?? "")
            ];

            $paymentMethod = trim($body["payment_method"] ?? "cash");

            if (empty($addressData['city']) || empty($addressData['street'])) {
                Response::json(422, ["success" => false, "message" => "City and Street are required."]);
            }

            // 2. Get Cart Items
            $cartModel = new Cart($pdo);
            $cartItems = $cartModel->getCartItems($userId);

            if (empty($cartItems)) {
                Response::json(400, ["success" => false, "message" => "Your cart is empty."]);
            }

            // 3. Server-Side Calculation
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $subtotal += ($item['price'] * $item['quantity']);
            }

            $deliveryFee = ($subtotal >= 100) ? 0.00 : 5.00; // Free delivery logic
            $discountAmount = 0.00; 
            $totalAmount = $subtotal + $deliveryFee - $discountAmount;

            $financialData = [
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount
            ];

            // 4. Create the Order (Passing paymentMethod)
            $orderId = $orderModel->createOrder($userId, $addressData, $financialData, $cartItems, $paymentMethod);

            Response::json(201, [
                "success" => true,
                "message" => "Order created successfully.",
                "order_id" => $orderId,
                "calculated_total" => $totalAmount
            ]);
            // 4. Handle Payment Method
            if ($paymentMethod === 'visa') {
                // STRIPE INTEGRATION
                require_once __DIR__ . '/../vendor/autoload.php';
                $stripeConfig = require_once __DIR__ . '/../config/stripe.php';
                \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

                // Prepare line items for Stripe
                $line_items = [];
                foreach ($cartItems as $item) {
                    $line_items[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => $item['product_name'] . ' (' . $item['size_ml'] . 'ml)'],
                            'unit_amount' => (int)($item['price'] * 100), // Stripe uses cents
                        ],
                        'quantity' => $item['quantity'],
                    ];
                }

                // Add delivery fee as a separate line item if applicable
                if ($deliveryFee > 0) {
                     $line_items[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => 'Delivery Fee'],
                            'unit_amount' => (int)($deliveryFee * 100),
                        ],
                        'quantity' => 1,
                    ];
                }

                try {
                    // Create Stripe Checkout Session
                    $session = \Stripe\Checkout\Session::create([
                        // 'payment_method_types' => ['card'],
                        'line_items' => $line_items,
                        'mode' => 'payment',
                        // Redirect URL after success (we will create success.php later)
                        'success_url' => 'http://localhost/perfume_ecommerce/success.php?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => 'http://localhost/perfume_ecommerce/checkout.php',
                        // Pass our data to retrieve it in success.php
                        'metadata' => [
                            'user_id' => $userId,
                            'city' => $addressData['city'],
                            'area' => $addressData['area'],
                            'street' => $addressData['street'],
                            'building' => $addressData['building'],
                            'subtotal' => $subtotal,
                            'delivery_fee' => $deliveryFee,
                            'total_amount' => $totalAmount
                        ]
                    ]);

                    // Return the Stripe URL to the frontend
                    Response::json(200, [
                        "success" => true,
                        "message" => "Redirecting to Stripe...",
                        "checkout_url" => $session->url 
                    ]);

                } catch (Exception $e) {
                    Response::json(500, ["success" => false, "message" => "Stripe error: " . $e->getMessage()]);
                }

            } else {
                // CASH ON DELIVERY (Normal flow)
                $orderModel = new Order($pdo);
                $orderId = $orderModel->createOrder($userId, $addressData, $financialData, $cartItems, 'cash');

                Response::json(201, [
                    "success" => true,
                    "message" => "Order created successfully.",
                    "order_id" => $orderId,
                    "calculated_total" => $totalAmount 
                ]);
            }
            break;
        // GET ORDERS (For Customer)
        case "GET":
            // $orderModel = new Order($pdo);
            // $orderModel->autoConfirmOrders();
            // // Fetch orders for the logged-in user
            // $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
            // $stmt->execute([':user_id' => $userId]);
            // $orders = $stmt->fetchAll();

            // Response::json(200, [
            //     "success" => true,
            //     "count" => count($orders),
            //     "data" => $orders
            // ]);
            // break;

            // GET ORDERS (For Customer: List or Tracking Details)
            if ($id !== null) {
                // Tracking Details (Single order + items)
                $orderData = $orderModel->getCustomerOrderDetails($userId, $id);

                if ($orderData) {
                    Response::json(200, ["success" => true, "data" => $orderData]);
                } else {
                    Response::json(404, ["success" => false, "message" => "Order not found or unauthorized"]);
                }
            } else {
                // My Orders (List summary)
                $orders = $orderModel->getOrdersByUser($userId);

                Response::json(200, [
                    "success" => true,
                    "count" => count($orders),
                    "data" => $orders
                ]);
            }
            break;

        // CANCEL ORDER (For Customer)
        case "PUT":
            if ($id === null) {
                Response::json(400, ["success" => false, "message" => "Order ID is required"]);
            }

            try {
                // Pass 'false' for $isAdmin because this is the customer route
                $orderModel->cancelOrder($id, false);

                Response::json(200, [
                    "success" => true,
                    "message" => "Order cancelled successfully. Stock has been returned."
                ]);
            } catch (Exception $e) {
                Response::json(400, [
                    "success" => false,
                    "message" => $e->getMessage()
                ]);
            }
            break;

        default:
            header("Allow: POST, GET, PUT");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
    }
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>
