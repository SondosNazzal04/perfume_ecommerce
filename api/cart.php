<?php
// File: api/cart.php


require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';

try{
// Initialize Database and Cart class
$db = new Database();
$pdo = $db->connect();
$cart = new Cart($pdo);

// For testing purposes, we will hardcode the user_id. Later you can use $_SESSION['user_id']
$userId = 1; 

$action = $_GET['action'] ?? $_POST['action'] ?? '';


    switch ($action) {
        case 'add':
            $variantId = $_POST['variant_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            if ($cart->addItem($userId, $variantId, $quantity)) {
                echo json_encode(["status" => "success", "message" => "Item added to cart"]);
            }
            else {
                echo json_encode(["status" => "error", "message" => "Failed to add item to cart"]);
            }
            break;

        case 'get':
            $items = $cart->getCartItems($userId);
            echo json_encode(["status" => "success", "data" => $items]);
            break;

        case 'update':
            $cartId = $_POST['cart_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            if ($cart->updateQuantity($cartId, $userId, $quantity)) {
                echo json_encode(["status" => "success", "message" => "Quantity updated"]);
            }
            else {
                echo json_encode(["status" => "error", "message" => "Failed to update quantity"]);
            }
            break;

        case 'remove':
            $cartId = $_POST['cart_id'] ?? 0;
            
            if ($cart->removeItem($cartId, $userId)) {
                echo json_encode(["status" => "success", "message" => "Item removed"]);
            }else {
                echo json_encode(["status" => "error", "message" => "Failed to remove item"]);
            }
            
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Invalid action"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>