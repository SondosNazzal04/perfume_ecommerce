<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log($e->getMessage());

    Response::json(500, [
        "success" => false,
        "message" => "Database connection error"
    ]);
}

$product = new Product($pdo);
$method = $_SERVER["REQUEST_METHOD"];

// Read and validate the JSON request body
$readProductBody = function (): array {
    try {
        $body = json_decode(
            file_get_contents("php://input"),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        Response::json(400, [
            "success" => false,
            "message" => "Invalid JSON body"
        ]);
    }

    if (!is_array($body)) {
        Response::json(400, [
            "success" => false,
            "message" => "JSON body must be an object"
        ]);
    }

    $brandId = filter_var(
        $body["brand_id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    $name = $body["name"] ?? null;
    $slug = $body["slug"] ?? null;
    $description = $body["description"] ?? null;
    $categoryIds = $body["category_ids"] ?? [];
    $isActive = $body["is_active"] ?? true;

    if ($brandId === false || $brandId === null) {
        Response::json(422, [
            "success" => false,
            "message" => "Valid brand_id is required"
        ]);
    }

    if (!is_string($name) || !is_string($slug)) {
        Response::json(422, [
            "success" => false,
            "message" => "Name and slug must be strings"
        ]);
    }

    $name = trim($name);
    $slug = trim($slug);

    if ($name === "" || $slug === "") {
        Response::json(422, [
            "success" => false,
            "message" => "Name and slug are required"
        ]);
    }

    if ($description !== null && !is_string($description)) {
        Response::json(422, [
            "success" => false,
            "message" => "Description must be a string or null"
        ]);
    }

    if (!is_array($categoryIds)) {
        Response::json(422, [
            "success" => false,
            "message" => "category_ids must be an array"
        ]);
    }

    $validCategoryIds = [];

    foreach ($categoryIds as $categoryId) {
        $validCategoryId = filter_var(
            $categoryId,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if ($validCategoryId === false) {
            Response::json(422, [
                "success" => false,
                "message" => "Every category ID must be a positive integer"
            ]);
        }

        $validCategoryIds[] = (int) $validCategoryId;
    }

    if (!is_bool($isActive) && !in_array($isActive, [0, 1, "0", "1"], true)) {
        Response::json(422, [
            "success" => false,
            "message" => "is_active must be true or false"
        ]);
    }

    return [
        "brand_id" => (int) $brandId,
        "name" => $name,
        "slug" => $slug,
        "description" => $description !== null && trim($description) !== ""
            ? trim($description)
            : null,
        "is_active" => (bool) $isActive,
        "category_ids" => array_values(array_unique($validCategoryIds))
    ];
};

try {
    switch ($method) {
        // GET: retrieve all products or one product by ID
        case "GET":
            if (isset($_GET["id"])) {
                $id = filter_var(
                    $_GET["id"],
                    FILTER_VALIDATE_INT,
                    ["options" => ["min_range" => 1]]
                );

                if ($id === false) {
                    Response::json(400, [
                        "success" => false,
                        "message" => "Invalid product ID"
                    ]);
                }

                $productData = $product->getById($id);

                if ($productData === null) {
                    Response::json(404, [
                        "success" => false,
                        "message" => "Product not found"
                    ]);
                }

                Response::json(200, [
                    "success" => true,
                    "data" => $productData
                ]);
            }

            $products = $product->getAll();

            Response::json(200, [
                "success" => true,
                "count" => count($products),
                "data" => $products
            ]);

            // POST: create a product
        case "POST":
            $data = $readProductBody();

            $newId = $product->addProduct(
                $data["brand_id"],
                $data["name"],
                $data["slug"],
                $data["description"],
                $data["is_active"],
                $data["category_ids"]
            );

            Response::json(201, [
                "success" => true,
                "message" => "Product created successfully",
                "data" => $product->getById($newId)
            ]);

            // PUT: update a product
        case "PUT":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid product ID is required"
                ]);
            }

            if ($product->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Product not found"
                ]);
            }

            $data = $readProductBody();

            $product->updateProduct(
                $id,
                $data["brand_id"],
                $data["name"],
                $data["slug"],
                $data["description"],
                $data["is_active"],
                $data["category_ids"]
            );

            Response::json(200, [
                "success" => true,
                "message" => "Product updated successfully",
                "data" => $product->getById($id)
            ]);

            // DELETE: delete a product
        case "DELETE":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid product ID is required"
                ]);
            }

            if ($product->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Product not found"
                ]);
            }

            /** @var array{deleted: bool, archived: bool, reason: string|null} $deleteResult */
            $deleteResult = $product->deleteProduct($id);

            if ($deleteResult["reason"] === "not_found") {
                Response::json(404, [
                    "success" => false,
                    "message" => "Product not found"
                ]);
            }

            if ($deleteResult["archived"]) {
                Response::json(200, [
                    "success" => true,
                    "message" => $deleteResult["reason"] === "order_history"
                        ? "Product has order history, so it was deactivated instead of deleted"
                        : "Product has reviews, so it was deactivated instead of deleted",
                    "data" => $product->getById($id)
                ]);
            }

            Response::json(200, [
                "success" => $deleteResult["deleted"],
                "message" => "Product and its variants were deleted successfully"
            ]);

        default:
            header("Allow: GET, POST, PUT, DELETE");

            Response::json(405, [
                "success" => false,
                "message" => "Method not allowed"
            ]);
    }
} catch (InvalidArgumentException $e) {
    Response::json(422, [
        "success" => false,
        "message" => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());

    if ($e->getCode() === "23000") {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if ($driverCode === 1451) {
            Response::json(409, [
                "success" => false,
                "message" => "Product is linked to variants or other records and cannot be deleted"
            ]);
        }

        if ($driverCode === 1452) {
            Response::json(422, [
                "success" => false,
                "message" => "Brand or category does not exist"
            ]);
        }

        Response::json(409, [
            "success" => false,
            "message" => "The product slug already exists"
        ]);
    }

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
