<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/ProductImage.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER["REQUEST_METHOD"];

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
        "success" => false,
        "message" => "Database connection error"
    ]);
}

$product = new Product($pdo);
$productImage = new ProductImage($pdo);

try {
    switch ($method) {
        // GET: get all images for a product, or one image by ID
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
                        "message" => "Invalid image ID"
                    ]);
                }

                $image = $productImage->getById($id);

                if ($image === null) {
                    Response::json(404, [
                        "success" => false,
                        "message" => "Image not found"
                    ]);
                }

                Response::json(200, [
                    "success" => true,
                    "data" => $image
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

            $images = $productImage->getByProductId($productId);

            Response::json(200, [
                "success" => true,
                "count" => count($images),
                "data" => $images
            ]);

        // POST: upload an image or add an image URL
        case "POST":
            $productId = filter_var(
                $_POST["product_id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($productId === false || $productId === null) {
                Response::json(422, [
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

            $hasUploadedFile = isset($_FILES["image"])
                && ($_FILES["image"]["error"] ?? UPLOAD_ERR_NO_FILE)
                    !== UPLOAD_ERR_NO_FILE;

            // This endpoint expects one image, not image[].
            if (
                $hasUploadedFile &&
                (
                    !is_int($_FILES["image"]["error"] ?? null) ||
                    !is_string($_FILES["image"]["tmp_name"] ?? null) ||
                    !is_int($_FILES["image"]["size"] ?? null)
                )
            ) {
                Response::json(422, [
                    "success" => false,
                    "message" => "Upload one image file"
                ]);
            }

            $imageUrl = $_POST["image_url"] ?? "";

            if (!is_string($imageUrl)) {
                Response::json(422, [
                    "success" => false,
                    "message" => "image_url must be a string"
                ]);
            }

            $imageUrl = trim($imageUrl);

            if ($hasUploadedFile && $imageUrl !== "") {
                Response::json(422, [
                    "success" => false,
                    "message" => "Send either an image file or image_url, not both"
                ]);
            }

            if (!$hasUploadedFile && $imageUrl === "") {
                Response::json(422, [
                    "success" => false,
                    "message" => "Upload an image file or provide image_url"
                ]);
            }

            $isPrimary = filter_var(
                $_POST["is_primary"] ?? "false",
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($isPrimary === null) {
                Response::json(422, [
                    "success" => false,
                    "message" => "is_primary must be true or false"
                ]);
            }

            $sortOrder = filter_var(
                $_POST["sort_order"] ?? 0,
                FILTER_VALIDATE_INT,
                [
                    "options" => [
                        "min_range" => 0,
                        "max_range" => 4294967295
                    ]
                ]
            );

            if ($sortOrder === false) {
                Response::json(422, [
                    "success" => false,
                    "message" => "sort_order must be an integer between 0 and 4294967295"
                ]);
            }

            if ($imageUrl !== "") {
                $newId = $productImage->addImageUrl(
                    $productId,
                    $imageUrl,
                    $isPrimary,
                    $sortOrder
                );
            } else {
                $newId = $productImage->addImage(
                    $productId,
                    $_FILES["image"],
                    $isPrimary,
                    $sortOrder
                );
            }

            Response::json(201, [
                "success" => true,
                "message" => "Product image uploaded successfully",
                "data" => $productImage->getById($newId)
            ]);

        // PUT: make an image the primary image
        case "PUT":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid image ID is required"
                ]);
            }

            if (!$productImage->setPrimary($id)) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Image not found"
                ]);
            }

            Response::json(200, [
                "success" => true,
                "message" => "Primary image updated successfully",
                "data" => $productImage->getById($id)
            ]);

        // DELETE: delete an image
        case "DELETE":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid image ID is required"
                ]);
            }

            $deleted = $productImage->deleteImage($id);

            if (!$deleted) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Image not found"
                ]);
            }

            Response::json(200, [
                "success" => true,
                "message" => "Product image deleted successfully"
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

    Response::json(500, [
        "success" => false,
        "message" => "A database error occurred"
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());

    Response::json(500, [
        "success" => false,
        "message" => "An image operation failed"
    ]);
}