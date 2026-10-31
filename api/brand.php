<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Brand.php';

require_once __DIR__ . '/../core/Response.php';


header("Content-Type: application/json; charset=UTF-8");

// 1. Connect to the database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, [
        "success" => false,
        "message" => "Database connection error: " . $e->getMessage()
    ]);
}

$brand = new Brand($pdo);

$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {

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
                    "message" => "Invalid brand ID"
                ]);
            }

            $brandData = $brand->getById($id);

            if ($brandData === null) {
                Response::json(404, [
                    "success" => false,
                    "message" => "Brand not found"
                ]);
            }

            Response::json(200, [
                "success" => true,
                "data" => $brandData
            ]);
        }

        $brands = $brand->getAll();

        Response::json(200, [
            "success" => true,
            "count" => count($brands),
            "data" => $brands
        ]);
        //////////////////////////////
    case "POST":
        $body = json_decode(file_get_contents("php://input"), true);

        if (!is_array($body)) {
            Response::json(400, [
                "success" => false,
                "message" => "Invalid JSON body"
            ]);
        }

        $name = trim($body["name"] ?? "");
        $description = trim($body["description"] ?? "");

        if ($name === "") {
            Response::json(422, [
                "success" => false,
                "message" => "Brand name is required"
            ]);
        }

        $created = $brand->addBrand($name, $description ?: null);

        Response::json(201, [
            "success" => $created,
            "message" => "Brand created successfully"
        ]);
        //////////////////////////
    case "PUT":
        $id = filter_var(
            $_GET["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if ($id === false || $id === null) {
            Response::json(400, [
                "success" => false,
                "message" => "Valid brand ID is required"
            ]);
        }

        $body = json_decode(file_get_contents("php://input"), true);

        if (!is_array($body)) {
            Response::json(400, [
                "success" => false,
                "message" => "Invalid JSON body"
            ]);
        }

        $name = trim($body["name"] ?? "");
        $description = trim($body["description"] ?? "");

        if ($name === "") {
            Response::json(422, [
                "success" => false,
                "message" => "Brand name is required"
            ]);
        }

        if ($brand->getById($id) === null) {
            Response::json(404, [
                "success" => false,
                "message" => "Brand not found"
            ]);
        }

        $brand->updateBrand($id, $name, $description ?: null);

        Response::json(200, [
            "success" => true,
            "message" => "Brand updated successfully"
        ]);
        ///////////////////////////
    case "DELETE":
        $id = filter_var(
            $_GET["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if ($id === false || $id === null) {
            Response::json(400, [
                "success" => false,
                "message" => "Valid brand ID is required"
            ]);
        }

        if ($brand->getById($id) === null) {
            Response::json(404, [
                "success" => false,
                "message" => "Brand not found"
            ]);
        }

        $deleted = $brand->deleteBrand($id);

        Response::json(200, [
            "success" => $deleted,
            "message" => "Brand deleted successfully"
        ]);

    default:
header("Allow: GET, POST, PUT, DELETE");        Response::json(405, [
            "success" => false,
            "message" => "Method not allowed"
        ]);
}
