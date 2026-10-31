<?php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Review.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database connection error"]);
}

$review = new Review($database);
$method = $_SERVER["REQUEST_METHOD"];
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

try {
    switch ($method) {
        case 'GET':
            $userIdFilter = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : null;
            $productIdFilter = isset($_GET['product_id']) ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT) : null;

            $data = $review->getAll($userIdFilter !== false ? $userIdFilter : null, $productIdFilter !== false ? $productIdFilter : null);
            Response::json(200, ["success" => true, "data" => $data]);
            break;

        case 'DELETE':
            if (!$id) {
                Response::json(400, ["success" => false, "message" => "Review ID is required"]);
            }

            if ($review->softDelete($id)) {
                Response::json(200, ["success" => true, "message" => "Review soft-deleted successfully"]);
            } else {
                Response::json(400, ["success" => false, "message" => "Failed to delete review"]);
            }
            break;

        default:
            header("Allow: GET, DELETE");
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
