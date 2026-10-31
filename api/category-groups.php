<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/CategoryGroup.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

// Connect to the database
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

$group = new CategoryGroup($pdo);
$method = $_SERVER["REQUEST_METHOD"];

// Read JSON request body
function getJsonBody(): array
{
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

    return $body;
}

// Validate group name and slug
function validateGroupData(array $body): array
{
    $name = $body["name"] ?? null;
    $slug = $body["slug"] ?? null;

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
        "name" => $name,
        "slug" => $slug
    ];
}

try {
    switch ($method) {
        // GET: retrieve all groups or one group by ID
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
                        "message" => "Invalid category group ID"
                    ]);
                }

                $categoryGroup = $group->getById($id);

                if ($categoryGroup === null) {
                    Response::json(404, [
                        "success" => false,
                        "message" => "Category group not found"
                    ]);
                }

                Response::json(200, [
                    "success" => true,
                    "data" => $categoryGroup
                ]);
            }

            $groups = $group->getAll();

            Response::json(200, [
                "success" => true,
                "count" => count($groups),
                "data" => $groups
            ]);

        // POST: create a group
        case "POST":
            $body = getJsonBody();
            $data = validateGroupData($body);

            $created = $group->addGroup(
                $data["name"],
                $data["slug"]
            );

            Response::json(201, [
                "success" => $created,
                "message" => "Category group created successfully"
            ]);

        // PUT: update a group
        case "PUT":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid category group ID is required"
                ]);
            }

            if ($group->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Category group not found"
                ]);
            }

            $body = getJsonBody();
            $data = validateGroupData($body);

            $group->updateGroup(
                $id,
                $data["name"],
                $data["slug"]
            );

            Response::json(200, [
                "success" => true,
                "message" => "Category group updated successfully"
            ]);

        // DELETE: delete a group
        case "DELETE":
            $id = filter_var(
                $_GET["id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($id === false || $id === null) {
                Response::json(400, [
                    "success" => false,
                    "message" => "Valid category group ID is required"
                ]);
            }

            if ($group->getById($id) === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Category group not found"
                ]);
            }

            $deleted = $group->deleteGroup($id);

            Response::json(200, [
                "success" => $deleted,
                "message" => "Category group deleted successfully"
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
            "message" => "Name or slug already exists, or the group is in use"
        ]);
    }

    Response::json(500, [
        "success" => false,
        "message" => "A database error occurred"
    ]);
}