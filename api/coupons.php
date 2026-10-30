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
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

function getJsonBody(): array
{
    $rawBody = file_get_contents("php://input");
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, ["success" => false, "message" => "Invalid JSON body"]);
    }
    if (!is_array($data)) {
        Response::json(400, ["success" => false, "message" => "JSON body must be an object"]);
    }
    return $data;
}

// Helper function to extract and validate data to avoid duplicating code in POST and PUT
function extractAndValidateCouponData(array $body): array
{
    $data = [
        'code' => trim($body["coupon-code"] ?? ''),
        'discount_type' => $body["coupon-discount-type"] ?? 'percentage',
        'discount_value' => (float) ($body["coupon-discount-value"] ?? 0),
        'usage_limit' => $body["coupon-usage-limit"] ?? null,
        'starts_at' => $body["coupon-start-at"] ?? null,
        'expires_at' => $body["coupon-expires-at"] ?? null,
        'min_order_amount' => (float) ($body["coupon-min-order-amount"] ?? 0.00),
        'is_active' => isset($body["coupon-is-active"]) ? (int) $body["coupon-is-active"] : 1
    ];

    if (empty($data['code'])) {
        Response::json(400, ["success" => false, "message" => "Coupon code is required"]);
    }
    if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
        Response::json(400, ["success" => false, "message" => "Discount type must be percentage or fixed"]);
    }
    if ($data['discount_value'] <= 0) {
        Response::json(400, ["success" => false, "message" => "Discount value must be greater than zero"]);
    }
    if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
        Response::json(400, ["success" => false, "message" => "Percentage discount cannot exceed 100"]);
    }

    return $data;
}

try {
    switch ($method) {
        case 'POST':
            $body = getJsonBody();
            $data = extractAndValidateCouponData($body);

            if ($coupon->create($data)) {
                Response::json(201, [
                    "success" => true,
                    "message" => "Coupon created successfully"
                ]);
            } else {
                Response::json(400, [
                    "success" => false,
                    "message" => "Failed to create coupon"
                ]);
            }
            break;

        case 'PUT':
            if (!$id) {
                Response::json(400, ["success" => false, "message" => "Valid Coupon ID is required for editing"]);
            }

            $body = getJsonBody();
            $data = extractAndValidateCouponData($body);

            if ($coupon->update($id, $data)) {
                Response::json(200, [
                    "success" => true,
                    "message" => "Coupon updated successfully"
                ]);
            } else {
                Response::json(400, [
                    "success" => false,
                    "message" => "Failed to update coupon or no changes made"
                ]);
            }
            break;

        case 'DELETE':
            if (!$id) {
                Response::json(400, ["success" => false, "message" => "Valid Coupon ID is required for deletion"]);
            }

            if ($coupon->delete($id)) {
                Response::json(200, [
                    "success" => true,
                    "message" => "Coupon deleted successfully"
                ]);
            } else {
                Response::json(400, [
                    "success" => false,
                    "message" => "Failed to delete coupon"
                ]);
            }
            break;

        default:
            header("Allow: POST, PUT, DELETE");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());

    // 23000 is the SQL state for Integrity constraint violation (like a duplicate UNIQUE code)
    if ($e->getCode() == 23000) {
        Response::json(409, [
            "success" => false,
            "message" => "Coupon code already exists or is in use."
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
?>
