<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../core/Response.php';

if (!isset($_SESSION['user_id'])) {
    Response::json(401, [
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

// Accept positive integers or integer strings, but reject arrays,
// booleans and floating-point values.
$positiveInteger = static function ($value): int|false {
    if (!is_int($value) && !is_string($value)) {
        return false;
    }

    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 4294967295]]
    );
};

$id = null;

if (isset($_GET['id'])) {
    $id = $positiveInteger($_GET['id']);

    if ($id === false) {
        Response::json(400, [
            'success' => false,
            'message' => 'Invalid cart item ID'
        ]);
    }
}

$readJsonBody = static function (): array {
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
            'message' => 'JSON body must be a JSON object'
        ]);
    }

    return (array) $data;
};

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Cart database connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $cart = new Cart($pdo);

    // Do not report success for a missing item or another user's item.
    if (
        $id !== null &&
        in_array($method, ['PUT', 'DELETE'], true)
    ) {
        $stmt = $pdo->prepare(
            'SELECT id FROM cart_items WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        if (!$stmt->fetch()) {
            Response::json(404, [
                'success' => false,
                'message' => 'Cart item not found'
            ]);
        }
    }

    switch ($method) {
        case 'GET':
            $items = $cart->getCartItems($userId);

            Response::json(200, [
                'success' => true,
                'count' => count($items),
                'data' => $items
            ]);

        case 'POST':
            $body = $readJsonBody();
            $variantId = $positiveInteger($body['variant_id'] ?? null);
            $quantity = $positiveInteger(
                array_key_exists('quantity', $body) ? $body['quantity'] : 1
            );

            if ($variantId === false || $quantity === false) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'variant_id and quantity must be positive integers'
                ]);
            }

            $stmt = $pdo->prepare(
                'SELECT v.id
                 FROM product_variants v
                 JOIN products p ON p.id = v.product_id
                 WHERE v.id = ? AND p.is_active = 1'
            );
            $stmt->execute([$variantId]);

            if (!$stmt->fetch()) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product variant not found or unavailable'
                ]);
            }

            $added = $cart->addItem($userId, $variantId, $quantity);

            Response::json($added ? 201 : 500, [
                'success' => $added,
                'message' => $added
                    ? 'Item added to cart successfully'
                    : 'Failed to add item to cart'
            ]);

        case 'PUT':
            if ($id === null) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Cart item ID is required in the URL'
                ]);
            }

            $body = $readJsonBody();
            $quantity = $positiveInteger($body['quantity'] ?? null);

            if ($quantity === false) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Quantity must be a positive integer'
                ]);
            }

            $updated = $cart->updateQuantity($id, $userId, $quantity);

            Response::json($updated ? 200 : 500, [
                'success' => $updated,
                'message' => $updated
                    ? 'Quantity updated successfully'
                    : 'Failed to update quantity'
            ]);

        case 'DELETE':
            if ($id === null) {
                $deleted = $cart->clearCart($userId);
                $message = 'Cart cleared successfully';
            } else {
                $deleted = $cart->removeItem($id, $userId);
                $message = 'Item removed from cart successfully';
            }

            Response::json($deleted ? 200 : 500, [
                'success' => $deleted,
                'message' => $deleted ? $message : 'Failed to remove cart items'
            ]);
    }
} catch (PDOException $e) {
    error_log('Cart database error: ' . $e->getMessage());

    $errorCode = (int) ($e->errorInfo[1] ?? 0);

    if (in_array($errorCode, [1264, 1690], true)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Quantity exceeds the supported limit'
        ]);
    }

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    error_log('Cart API error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}