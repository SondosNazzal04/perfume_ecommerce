<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/ProductVariant.php';
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
$variant = new ProductVariant($pdo);
$method = $_SERVER["REQUEST_METHOD"];

// Read and validate the JSON request body
$readVariantBody = function (): array {
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

    $productId = filter_var(
        $body["product_id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    $sizeMl = filter_var(
        $body["size_ml"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 65535]]
    );

    $price = $body["price"] ?? null;
    $discountPrice = $body["discount_price"] ?? null;
    $stock = filter_var(
        $body["stock"] ?? 0,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 0]]
    );

    if ($productId === false || $productId === null) {
        Response::json(422, [
            "success" => false,
            "message" => "Valid product_id is required"
        ]);
    }

    if ($sizeMl === false || $sizeMl === null) {
        Response::json(422, [
            "success" => false,
            "message" => "size_ml must be between 1 and 65535"
        ]);
    }

    if (!is_numeric($price) || is_bool($price) || (float) $price <= 0) {
        Response::json(422, [
            "success" => false,
            "message" => "Price must be a number greater than zero"
        ]);
    }

    if (
        $discountPrice !== null
        && $discountPrice !== ""
        && (
            !is_numeric($discountPrice)
            || is_bool($discountPrice)
            || (float) $discountPrice <= 0
            || (float) $discountPrice >= (float) $price
        )
    ) {
        Response::json(422, [
            "success" => false,
            "message" => "Discount price must be greater than zero and lower than price"
        ]);
    }

    if ($stock === false) {
        Response::json(422, [
            "success" => false,
            "message" => "Stock must be zero or greater"
        ]);
    }

    return [
        "product_id" => (int) $productId,
        "size_ml" => (int) $sizeMl,
        "price" => (float) $price,
        "discount_price" => $discountPrice === null || $discountPrice === ""
            ? null
            : (float) $discountPrice,
        "stock" => (int) $stock
    ];
};

try {
    switch ($method) {
        // GET: retrieve one variant by ID, or all variants for a product
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
                        "message" => "Invalid variant ID"
                    ]);
                }

                $variantData = $variant->getById($id);

                if ($variantData === null) {
                    Response::json(404, [
                        "success" => false,
                        "message" => "Variant not found"
                    ]);
                }

                Response::json(200, [
                    "success" => true,
                    "data" => $variantData
                ]);
            }

            $productId = filter_var(
                $_GET["product_id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($productId === false || $productId === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid product_id is required"
                ]);
            }

            if ($product->getById($productId) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Product not found"
                ]);
            }

            $variants = $variant->getByProductId($productId);

            Response::json(200, [
                "success" => true,
                "count" => count($variants),
                "data" => $variants
            ]);

            // POST: add a variant
        case "POST":
            $data = $readVariantBody();

            if ($product->getById($data["product_id"]) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Product not found"
                ]);
            }

            $newId = $variant->addVariant(
                $data["product_id"],
                $data["size_ml"],
                $data["price"],
                $data["discount_price"],
                $data["stock"]
            );

            Response::json(201, [
                "success" => true,
                "message" => "Product variant created successfully",
                "data" => $variant->getById($newId)
            ]);

            // PUT: update a variant
        case "PUT":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid variant ID is required"
                ]);
            }

            if ($variant->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Variant not found"
                ]);
            }

            $data = $readVariantBody();

            if ($product->getById($data["product_id"]) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Product not found"
                ]);
            }

            $variant->updateVariant(
                $id,
                $data["product_id"],
                $data["size_ml"],
                $data["price"],
                $data["discount_price"],
                $data["stock"]
            );

            Response::json(200, [
                "success" => true,
                "message" => "Product variant updated successfully",
                "data" => $variant->getById($id)
            ]);

            // DELETE: delete a variant
        case "DELETE":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid variant ID is required"
                ]);
            }

            if ($variant->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Variant not found"
                ]);
            }

            $deleted = $variant->deleteVariant($id);

            Response::json(200, [
                "success" => $deleted,
                "message" => "Product variant deleted successfully"
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

        if ($driverCode === 1062) {
            Response::json(409, [
                "success" => false,
                "message" => "This product already has a variant with that size"
            ]);
        }

        if ($driverCode === 1451) {
            Response::json(409, [
                "success" => false,
                "message" => "This variant is linked to a cart or order and cannot be deleted"
            ]);
        }

        Response::json(409, [
            "success" => false,
            "message" => "The request conflicts with a database relationship"
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
