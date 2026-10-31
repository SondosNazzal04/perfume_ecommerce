<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/ProductVariant.php';
require_once __DIR__ . '/../core/Response.php';

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    if (!isset($_SESSION['user_id'])) {
        Response::json(401, [
            'success' => false,
            'message' => 'Unauthorized'
        ]);
    }

    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        Response::json(403, [
            'success' => false,
            'message' => 'Access denied: admins only'
        ]);
    }
}

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log($e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

$product = new Product($pdo);
$variant = new ProductVariant($pdo);

// Reject booleans, arrays and floats for integer fields.
$readInteger = static function ($value, int $min, int $max): int|false {
    if (!is_int($value) && !is_string($value)) {
        return false;
    }

    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => $min, 'max_range' => $max]]
    );
};

$readVariantBody = function () use ($readInteger): array {
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

    $body = (array) $body;

    $productId = $readInteger(
        $body['product_id'] ?? null,
        1,
        4294967295
    );

    $sizeMl = $readInteger(
        $body['size_ml'] ?? null,
        1,
        65535
    );

    $stock = $readInteger(
        array_key_exists('stock', $body) ? $body['stock'] : 0,
        0,
        4294967295
    );

    if ($productId === false) {
        Response::json(422, [
            'success' => false,
            'message' => 'Valid product_id is required'
        ]);
    }

    if ($sizeMl === false) {
        Response::json(422, [
            'success' => false,
            'message' => 'size_ml must be an integer between 1 and 65535'
        ]);
    }

    if ($stock === false) {
        Response::json(422, [
            'success' => false,
            'message' => 'Stock must be an integer between 0 and 4294967295'
        ]);
    }

    // Match DECIMAL(10,2) without silently rounding extra decimals.
    $readPrice = static function ($value, string $field): float {
        if (
            (!is_int($value) && !is_float($value) && !is_string($value)) ||
            !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $value) ||
            (float) $value <= 0
        ) {
            Response::json(422, [
                'success' => false,
                'message' => $field
                    . ' must be between 0.01 and 99999999.99 with at most 2 decimal places'
            ]);
        }

        return (float) $value;
    };

    $price = $readPrice($body['price'] ?? null, 'Price');
    $discountPrice = $body['discount_price'] ?? null;

    if ($discountPrice === '') {
        $discountPrice = null;
    }

    if ($discountPrice !== null) {
        $discountPrice = $readPrice($discountPrice, 'Discount price');

        if ($discountPrice >= $price) {
            Response::json(422, [
                'success' => false,
                'message' => 'Discount price must be lower than price'
            ]);
        }
    }

    return [
        'product_id' => $productId,
        'size_ml' => $sizeMl,
        'price' => $price,
        'discount_price' => $discountPrice,
        'stock' => $stock
    ];
};

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = $readInteger($_GET['id'], 1, 4294967295);

                if ($id === false) {
                    Response::json(400, [
                        'success' => false,
                        'message' => 'Invalid variant ID'
                    ]);
                }

                $variantData = $variant->getById($id);

                if ($variantData === null) {
                    Response::json(404, [
                        'success' => false,
                        'message' => 'Variant not found'
                    ]);
                }

                Response::json(200, [
                    'success' => true,
                    'data' => $variantData
                ]);
            }

            $productId = $readInteger(
                $_GET['product_id'] ?? null,
                1,
                4294967295
            );

            if ($productId === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid product_id is required'
                ]);
            }

            if ($product->getById($productId) === null) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product not found'
                ]);
            }

            $variants = $variant->getByProductId($productId);

            Response::json(200, [
                'success' => true,
                'count' => count($variants),
                'data' => $variants
            ]);

        case 'POST':
            $data = $readVariantBody();

            if ($product->getById($data['product_id']) === null) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product not found'
                ]);
            }

            $newId = $variant->addVariant(
                $data['product_id'],
                $data['size_ml'],
                $data['price'],
                $data['discount_price'],
                $data['stock']
            );

            Response::json(201, [
                'success' => true,
                'message' => 'Product variant created successfully',
                'data' => $variant->getById($newId)
            ]);

        case 'PUT':
            $id = $readInteger($_GET['id'] ?? null, 1, 4294967295);

            if ($id === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid variant ID is required'
                ]);
            }

            $existingVariant = $variant->getById($id);

            if ($existingVariant === null) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Variant not found'
                ]);
            }

            $data = $readVariantBody();

            // Moving a variant changes the product referenced by existing
            // cart items and order items. Keep it attached to its product.
            if ($data['product_id'] !== (int) $existingVariant['product_id']) {
                Response::json(409, [
                    'success' => false,
                    'message' => 'A variant cannot be moved to another product'
                ]);
            }

            $updated = $variant->updateVariant(
                $id,
                $data['product_id'],
                $data['size_ml'],
                $data['price'],
                $data['discount_price'],
                $data['stock']
            );

            if (!$updated) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to update variant'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Product variant updated successfully',
                'data' => $variant->getById($id)
            ]);

        case 'DELETE':
            $id = $readInteger($_GET['id'] ?? null, 1, 4294967295);

            if ($id === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid variant ID is required'
                ]);
            }

            if (!$variant->deleteVariant($id)) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Variant not found'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Product variant deleted successfully'
            ]);

        default:
            header('Allow: GET, POST, PUT, DELETE');

            Response::json(405, [
                'success' => false,
                'message' => 'Method not allowed'
            ]);
    }
} catch (InvalidArgumentException $e) {
    Response::json(422, [
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());

    $driverCode = (int) ($e->errorInfo[1] ?? 0);

    if ($driverCode === 1062) {
        Response::json(409, [
            'success' => false,
            'message' => 'This product already has a variant with that size'
        ]);
    }

    if ($driverCode === 1451) {
        Response::json(409, [
            'success' => false,
            'message' => 'This variant is linked to a cart or order and cannot be deleted'
        ]);
    }

    if ($driverCode === 1452) {
        Response::json(409, [
            'success' => false,
            'message' => 'The referenced product no longer exists'
        ]);
    }

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}