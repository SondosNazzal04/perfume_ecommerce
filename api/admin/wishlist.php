<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../classes/Wishlist.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database connection error"]);
}

$wishlist = new Wishlist($database);
$method = $_SERVER["REQUEST_METHOD"];

try {
    switch ($method) {
        case 'GET':
            // Later, add your admin authentication/JWT check here

            $data = $wishlist->getAll();

            // The frontend can count the total items by checking the length of $data
            Response::json(200, [
                "success" => true,
                "total_count" => count($data),
                "data" => $data
            ]);
            break;

        default:
            header("Allow: GET");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    Response::json(500, ["success" => false, "message" => "A database error occurred"]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::json(500, ["success" => false, "message" => "An internal server error occurred"]);
}
?>
