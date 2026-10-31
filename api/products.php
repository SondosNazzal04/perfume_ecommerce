<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../core/Response.php';

$method = $_SERVER['REQUEST_METHOD'];

$isAdmin = isset($_SESSION['user_id'])
    && ($_SESSION['user_role'] ?? '') === 'admin';

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    if (!isset($_SESSION['user_id'])) {
        Response::json(401, [
            'success' => false,
            'message' => 'Unauthorized'
        ]);
    }

    if (!$isAdmin) {
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

$readPositiveId = static function ($value): int|false {
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

$readProductBody = function () use ($readPositiveId): array {
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

    $brandId = $readPositiveId($body['brand_id'] ?? null);
    $name = $body['name'] ?? null;
    $slug = $body['slug'] ?? null;
    $description = $body['description'] ?? null;
    $categoryIds = $body['category_ids'] ?? [];
    $isActive = $body['is_active'] ?? true;

    if ($brandId === false) {
        Response::json(422, [
            'success' => false,
            'message' => 'Valid brand_id is required'
        ]);
    }

    if (!is_string($name) || !is_string($slug)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Name and slug must be strings'
        ]);
    }

    $name = trim($name);
    $slug = trim($slug);

    if ($name === '' || $slug === '') {
        Response::json(422, [
            'success' => false,
            'message' => 'Name and slug are required'
        ]);
    }

    if (
        mb_strlen($name, 'UTF-8') > 150 ||
        mb_strlen($slug, 'UTF-8') > 180
    ) {
        Response::json(422, [
            'success' => false,
            'message' => 'Name must not exceed 150 characters and slug 180 characters'
        ]);
    }

    if ($description !== null && !is_string($description)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Description must be a string or null'
        ]);
    }

    if ($description !== null) {
        $description = trim($description);

        // MySQL TEXT supports up to 65535 bytes.
        if (strlen($description) > 65535) {
            Response::json(422, [
                'success' => false,
                'message' => 'Description exceeds the supported length'
            ]);
        }

        if ($description === '') {
            $description = null;
        }
    }

    if (!is_array($categoryIds)) {
        Response::json(422, [
            'success' => false,
            'message' => 'category_ids must be an array'
        ]);
    }

    $validCategoryIds = [];

    foreach ($categoryIds as $categoryId) {
        $validCategoryId = $readPositiveId($categoryId);

        if ($validCategoryId === false) {
            Response::json(422, [
                'success' => false,
                'message' => 'Every category ID must be a positive integer'
            ]);
        }

        $validCategoryIds[] = $validCategoryId;
    }

    if (!in_array($isActive, [true, false, 0, 1, '0', '1'], true)) {
        Response::json(422, [
            'success' => false,
            'message' => 'is_active must be true or false'
        ]);
    }

    return [
        'brand_id' => $brandId,
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'is_active' => (bool) $isActive,
        'category_ids' => array_values(array_unique($validCategoryIds))
    ];
};

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = $readPositiveId($_GET['id']);

                if ($id === false) {
                    Response::json(400, [
                        'success' => false,
                        'message' => 'Invalid product ID'
                    ]);
                }

                $productData = $product->getById($id);

                if (
                    $productData === null ||
                    (!$isAdmin && (int) $productData['is_active'] !== 1)
                ) {
                    Response::json(404, [
                        'success' => false,
                        'message' => 'Product not found'
                    ]);
                }

                Response::json(200, [
                    'success' => true,
                    'data' => $productData
                ]);
            }

            $products = $product->getAll();

            if (!$isAdmin) {
                $products = array_values(array_filter(
                    $products,
                    static fn(array $item): bool =>
                        (int) $item['is_active'] === 1
                ));
            }

            Response::json(200, [
                'success' => true,
                'count' => count($products),
                'data' => $products
            ]);

        case 'POST':
            $data = $readProductBody();

            $newId = $product->addProduct(
                $data['brand_id'],
                $data['name'],
                $data['slug'],
                $data['description'],
                $data['is_active'],
                $data['category_ids']
            );

            Response::json(201, [
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->getById($newId)
            ]);

        case 'PUT':
            $id = $readPositiveId($_GET['id'] ?? null);

            if ($id === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid product ID is required'
                ]);
            }

            if ($product->getById($id) === null) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product not found'
                ]);
            }

            $data = $readProductBody();

            $updated = $product->updateProduct(
                $id,
                $data['brand_id'],
                $data['name'],
                $data['slug'],
                $data['description'],
                $data['is_active'],
                $data['category_ids']
            );

            if (!$updated) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to update product'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->getById($id)
            ]);

        case 'DELETE':
            $id = $readPositiveId($_GET['id'] ?? null);

            if ($id === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid product ID is required'
                ]);
            }

            $deleteResult = $product->deleteProduct($id);

            if ($deleteResult['reason'] === 'not_found') {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Product not found'
                ]);
            }

            if ($deleteResult['archived']) {
                Response::json(200, [
                    'success' => true,
                    'message' => $deleteResult['reason'] === 'order_history'
                        ? 'Product has order history, so it was deactivated instead of deleted'
                        : 'Product has reviews, so it was deactivated instead of deleted',
                    'data' => $product->getById($id)
                ]);
            }

            if (!$deleteResult['deleted']) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to delete product'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Product and its variants were deleted successfully'
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
            'message' => 'The product slug already exists'
        ]);
    }

    if ($driverCode === 1451) {
        Response::json(409, [
            'success' => false,
            'message' => 'Product is linked to other records and cannot be deleted'
        ]);
    }

    if ($driverCode === 1452) {
        Response::json(422, [
            'success' => false,
            'message' => 'Brand or category does not exist'
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