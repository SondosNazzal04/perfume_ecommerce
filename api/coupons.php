<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Coupon.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, [
        "success" => false,
        "message" => "Database connection error: " . $e->getMessage()
    ]);
}

$coupon = new Coupon($database);

$method = $_SERVER["REQUEST_METHOD"];

function getJsonBody(): array
{
    $rawBody = file_get_contents("php://input");

    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, [
            "success" => false,
            "message" => "Invalid JSON body"
        ]);
    }

    if (!is_array($data)) {
        Response::json(400, [
            "success" => false,
            "message" => "JSON body must be a JSON object"
        ]);
    }

    return $data;
}

try {
	switch ($method) {
		case 'POST':
			$body = getJsonBody();
			// FROM THE HTML BODY
			$data = array(
				'code' => $body["coupon-code"],
				'discount_type' => $body["coupon-discount-type"],
				'discount_value' => $body["coupon-discount-value"],
				'usage_limit' => $body["coupon-usage-limit"],
				'starts_at' => $body["coupon-start-at"],
				'expires_at' => $body["coupon-expires-at"],
				'min_order_amount' => $body["coupon-min-order-amount"],
				'is_active' => $body["coupon-is-active"]
			);

			if ($coupon->create($data)) {
				Response::json(201, [
					"success" => true,
					"message" => "Item added to cart successfully"
				]);
			} else {
				Response::json(400, [
					"success" => false,
					"message" => "Failed to add item to cart"
				]);
			}
			break;
	}
}
catch (PDOException $e) {
	error_log($e->getMessage());
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
?>
