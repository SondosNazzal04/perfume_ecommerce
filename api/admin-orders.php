<?php
// File: api/admin-orders.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

// Connect to Database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

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
        // GET: Retrieve all orders OR a single order (For Admin)
        case "GET":
            if ($id !== null) {
                $order = $orderModel->getOrderDetails($id);
                if ($order) {
                    Response::json(200, ["success" => true, "data" => $order]);
                } else {
                    Response::json(404, ["success" => false, "message" => "Order not found"]);
                }
            } else {
                $orders = $orderModel->getAllOrders();
                Response::json(200, ["success" => true, "count" => count($orders), "data" => $orders]);
            }
            break;

        // PUT: Update order status
        case "PUT":
            if ($id === null) {
                Response::json(400, ["success" => false, "message" => "Order ID is required"]);
            }
            $body = getJsonBody();
            $newStatus = trim($body["status"] ?? "");

            if (empty($newStatus)) {
                Response::json(422, ["success" => false, "message" => "Status is required"]);
            }

            if ($orderModel->updateOrderStatus($id, $newStatus)) {
                Response::json(200, ["success" => true, "message" => "Order status updated to: " . $newStatus]);
            } else {
                Response::json(400, ["success" => false, "message" => "Failed to update status. Ensure status is valid."]);
            }
            break;

        default:
            header("Allow: GET, PUT");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
    }
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>