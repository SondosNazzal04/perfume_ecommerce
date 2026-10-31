<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/CategoryGroup.php';
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

$category = new Category($pdo);
$categoryGroup = new CategoryGroup($pdo);
$method = $_SERVER["REQUEST_METHOD"];

// Read and validate JSON request data
$readCategoryBody = function (): array {
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

    $groupId = filter_var(
        $body["group_id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    $name = $body["name"] ?? null;
    $slug = $body["slug"] ?? null;

    if ($groupId === false || $groupId === null) {
        Response::json(422, [
            "success" => false,
            "message" => "Valid group_id is required"
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

    return [
        "group_id" => $groupId,
        "name" => $name,
        "slug" => $slug
    ];
};

try {
    switch ($method) {
        // GET: retrieve all categories or one category by ID
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
                        "message" => "Invalid category ID"
                    ]);
                }

                $categoryData = $category->getById($id);

                if ($categoryData === null) {
                    Response::json(404, [
                        "success" => false,
                        "message" => "Category not found"
                    ]);
                }

                Response::json(200, [
                    "success" => true,
                    "data" => $categoryData
                ]);
            }

            $categories = $category->getAll();

            Response::json(200, [
                "success" => true,
                "count" => count($categories),
                "data" => $categories
            ]);

            // POST: create a category
        case "POST":
            $data = $readCategoryBody();

            if ($categoryGroup->getById($data["group_id"]) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Category group not found"
                ]);
            }

            $created = $category->addCategory(
                $data["group_id"],
                $data["name"],
                $data["slug"]
            );

            Response::json(201, [
                "success" => $created,
                "message" => "Category created successfully"
            ]);

            // PUT: update a category
        case "PUT":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid category ID is required"
                ]);
            }

            if ($category->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Category not found"
                ]);
            }

            $data = $readCategoryBody();

            if ($categoryGroup->getById($data["group_id"]) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Category group not found"
                ]);
            }

            $category->updateCategory(
                $id,
                $data["group_id"],
                $data["name"],
                $data["slug"]
            );

            Response::json(200, [
                "success" => true,
                "message" => "Category updated successfully"
            ]);

            // DELETE: delete a category
        case "DELETE":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid category ID is required"
                ]);
            }

            if ($category->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Category not found"
                ]);
            }

            $deleted = $category->deleteCategory($id);

            Response::json(200, [
                "success" => $deleted,
                "message" => "Category deleted successfully"
            ]);

        default:
            header("Allow: GET, POST, PUT, DELETE");

            Response::json(405, [
                "success" => false,
                "message" => "Method not allowed"
            ]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());

    if ($e->getCode() === "23000") {
        Response::json(409, [
            "success" => false,
            "message" => "Slug already exists in this group, or the category is in use"
        ]);
    }

    Response::json(500, [
        "success" => false,
        "message" => "A database error occurred"
    ]);
}
