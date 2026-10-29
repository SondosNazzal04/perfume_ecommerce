<?php

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

// Temporary user ID for testing (Will be $_SESSION['user_id'] later)
$userId = 1; 

$method = $_SERVER["REQUEST_METHOD"];

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
    switch ($method) {
        // CREATE ORDER (CHECKOUT)
        case "POST":
            $body = getJsonBody();

            // 1. Validate Address (Only address is received from the client)
            $addressData = [
                'city' => trim($body["city"] ?? ""),
                'area' => trim($body["area"] ?? ""),
                'street' => trim($body["street"] ?? ""),
                'building' => trim($body["building"] ?? "")
            ];

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
                // Multiply the real price from the database by the quantity
                $subtotal += ($item['price'] * $item['quantity']); 
            }

            $deliveryFee = 5.00; // Can be made dynamic based on city later
            $discountAmount = 0.00; // Will be updated when coupon logic is added
            $totalAmount = $subtotal + $deliveryFee - $discountAmount;

            // 4. Prepare Financial Data
            $financialData = [
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount
            ];

            // 5. Create the Order
            $orderModel = new Order($pdo);
            $orderId = $orderModel->createOrder($userId, $addressData, $financialData, $cartItems);

            // 6. Return success response with the calculated total
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

        default:
            header("Allow: POST, GET");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
    }
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>