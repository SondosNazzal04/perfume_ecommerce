<?php
// File: api/orders.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

// 1. Connect to Database
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

            // Get Payment Method (Default to cash if not provided)
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

            $deliveryFee = 5.00;
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
            break;

        // GET ORDERS (For Customer)
        case "GET":
            $orderModel = new Order($pdo);
            $orderModel->autoConfirmOrders();
            // Fetch orders for the logged-in user
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $userId]);
            $orders = $stmt->fetchAll();

            Response::json(200, [
                "success" => true,
                "count" => count($orders),
                "data" => $orders
            ]);
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