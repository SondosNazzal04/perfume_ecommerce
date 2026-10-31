<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Wishlist.php';

if (!isset($_SESSION['user_id'])) {
    Response::json(401, [
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
    header('Allow: GET, POST, DELETE');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$readProductId = static function ($value): int|false {
    if (!is_int($value) && !is_string($value)) {
        return false;
    }

    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 4294967295
            ]
        ]
    );
};

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Wishlist connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $wishlist = new Wishlist($database);

    switch ($method) {
        case 'GET':
            $data = $wishlist->getByUser($userId);

            Response::json(200, [
                'success' => true,
                'data' => $data
            ]);

        case 'POST':
            try {
                $body = json_decode(
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

            if (!$body instanceof stdClass) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'JSON body must be an object'
                ]);
            }

            $productId = $readProductId($body->product_id ?? null);

            if ($productId === false) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'product_id must be a positive integer'
                ]);
            }

            $stmt = $pdo->prepare(
                'SELECT id FROM products WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$productId]);

            if (!$stmt->fetch()) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product not found or unavailable'
                ]);
            }

            if (!$wishlist->add($userId, $productId)) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to add product to wishlist'
                ]);
            }

            Response::json(201, [
                'success' => true,
                'message' => 'Product added to wishlist'
            ]);

        case 'DELETE':
            $productId = $readProductId($_GET['product_id'] ?? null);

            if ($productId === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid product_id is required'
                ]);
            }

            if (!$wishlist->remove($userId, $productId)) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product not found in wishlist'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Product removed from wishlist'
            ]);
    }
} catch (PDOException $e) {
    error_log('Wishlist database error: ' . $e->getMessage());

    $driverCode = (int) ($e->errorInfo[1] ?? 0);

    if ($driverCode === 1062) {
        Response::json(409, [
            'success' => false,
            'message' => 'Product is already in your wishlist.'
        ]);
    }

    if ($driverCode === 1452) {
        Response::json(409, [
            'success' => false,
            'message' => 'The referenced user or product no longer exists'
        ]);
    }

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    error_log('Wishlist API error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}