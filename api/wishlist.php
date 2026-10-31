<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Wishlist.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database connection error"]);
}

$wishlist = new Wishlist($database);
$method = $_SERVER["REQUEST_METHOD"];

// Hardcoded for testing. Replace with your session/auth logic later.
$userId = 1;

function getJsonBody(): array {
    $rawBody = file_get_contents("php://input");
    if (!$rawBody) return [];
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, ["success" => false, "message" => "Invalid JSON body"]);
    }
    return is_array($data) ? $data : [];
}

try {
    switch ($method) {
        case 'GET':
            $data = $wishlist->getByUser($userId);
            Response::json(200, ["success" => true, "data" => $data]);
            break;

        case 'POST':
            $body = getJsonBody();
            $productId = isset($body['product_id']) ? (int) $body['product_id'] : 0;

            if (!$productId) {
                Response::json(400, ["success" => false, "message" => "product_id is required"]);
            }

            $wishlist->add($userId, $productId);
            Response::json(201, ["success" => true, "message" => "Product added to wishlist"]);
            break;

        case 'DELETE':
            // Use query parameters for DELETE requests (e.g., ?product_id=1)
            $productId = isset($_GET['product_id']) ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT) : 0;

            if (!$productId) {
                Response::json(400, ["success" => false, "message" => "product_id is required"]);
            }

            if ($wishlist->remove($userId, $productId)) {
                Response::json(200, ["success" => true, "message" => "Product removed from wishlist"]);
            } else {
                Response::json(404, ["success" => false, "message" => "Product not found in wishlist"]);
            }
            break;

        default:
            header("Allow: GET, POST, DELETE");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());

    // Traps the UNIQUE (user_id, product_id) violation
    if ($e->getCode() == 23000) {
        Response::json(409, ["success" => false, "message" => "Product is already in your wishlist."]);
    }

    Response::json(500, ["success" => false, "message" => "A database error occurred"]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Response::json(500, ["success" => false, "message" => "An internal server error occurred"]);
}
?>
