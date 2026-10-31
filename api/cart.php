<?php


require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");


//Check Authentication BEFORE connecting to the database
session_start();
if (!isset($_SESSION['user_id'])) {
    Response::json(401, ["success" => false, "message" => "Unauthorized. Please login."]);
    exit;
}
$userId = $_SESSION['user_id']; 
// 1. Connect to the database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, [
        "success" => false,
        "message" => "Database connection error: " . $e->getMessage()
    ]);
}

// Initialize Cart model
$cart = new Cart($pdo);


// 2. Get HTTP Method and ID from URL
$method = $_SERVER["REQUEST_METHOD"];
$id = null;

// Validate ID if provided in the URL (e.g., ?id=1)
if (isset($_GET["id"])) {
    $id = filter_var(
        $_GET["id"],
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($id === false) {
        Response::json(400, [
            "success" => false,
            "message" => "Invalid cart item ID"
        ]);
    }
}

// 3. Helper function to read JSON body 
function getJsonBody(): array
{
    $rawBody = file_get_contents("php://input");

    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, [
            "success" => false,
            "message" => "Invalid JSON body"
        ]);
    }

    if (!is_array($data)) {
        Response::json(400, [
            "success" => false,
            "message" => "JSON body must be a JSON object"
        ]);
    }

    return $data;
}

// 4. Handle the Request based on HTTP Method
try {
    switch ($method) {

        // GET: Retrieve cart items
        case "GET":
            $items = $cart->getCartItems($userId);
            
            Response::json(200, [
                "success" => true,
                "count" => count($items),
                "data" => $items
            ]);
            break;

        // POST: Add a new item to the cart
        case "POST":
            $body = getJsonBody();
            $variantId = $body["variant_id"] ?? 0;
            $quantity = $body["quantity"] ?? 1;

            if ($variantId <= 0 || $quantity <= 0) {
                Response::json(422, [
                    "success" => false,
                    "message" => "Valid variant_id and quantity are required"
                ]);
            }

            if ($cart->addItem($userId, $variantId, $quantity)) {
                Response::json(201, [
                    "success" => true,
                    "message" => "Item added to cart successfully"
                ]);
            } else {
                Response::json(400, [
                    "success" => false,
                    "message" => "Failed to add item to cart"
                ]);
            }
            break;

        // PUT: Update item quantity in the cart
        case "PUT":
            if ($id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Cart item ID is required in the URL (e.g., ?id=1)"
                ]);
            }

            $body = getJsonBody();
            $quantity = $body["quantity"] ?? 1;

            if ($quantity <= 0) {
                Response::json(422, [
                    "success" => false,
                    "message" => "Quantity must be greater than 0"
                ]);
            }

            if ($cart->updateQuantity($id, $userId, $quantity)) {
                Response::json(200, [
                    "success" => true,
                    "message" => "Quantity updated successfully"
                ]);
            } else {
                Response::json(400, [
                    "success" => false,
                    "message" => "Failed to update quantity"
                ]);
            }
            break;

        // DELETE: Remove an item OR clear the whole cart
        case "DELETE":
            // If no ID is provided in the URL, clear the entire cart
            if ($id === null) {
                if ($cart->clearCart($userId)) {
                    Response::json(200, [
                        "success" => true,
                        "message" => "Cart cleared successfully"
                    ]);
                } else {
                    Response::json(400, [
                        "success" => false,
                        "message" => "Failed to clear cart"
                    ]);
                }
            } else {
                // If ID is provided, remove only that specific item
                if ($cart->removeItem($id, $userId)) {
                    Response::json(200, [
                        "success" => true,
                        "message" => "Item removed from cart successfully"
                    ]);
                } else {
                    Response::json(400, [
                        "success" => false,
                        "message" => "Failed to remove item"
                    ]);
                }
            }
            break;

        // DEFAULT: Method not allowed
        default:
            header("Allow: GET, POST, PUT, DELETE");
            Response::json(405, [
                "success" => false,
                "message" => "Method not allowed"
            ]);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    Response::json(500, [
        "success" => false,
        "message" => "A database error occurred"
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::json(500, [
        "success" => false,
        "message" => "An internal server error occurred"
    ]);
}