<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Coupon.php';

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

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    header('Allow: POST, PUT, DELETE');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$id = null;

if (in_array($method, ['PUT', 'DELETE'], true)) {
    $id = filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false || $id === null) {
        Response::json(400, [
            'success' => false,
            'message' => 'A positive coupon ID is required'
        ]);
    }
}

function getJsonBody(): array
{
    try {
        $body = json_decode(
            file_get_contents('php://input'),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        Response::json(400, [
            'success' => false,
            'message' => 'Invalid JSON body'
        ]);
    }

    if (!$body instanceof stdClass) {
        Response::json(400, [
            'success' => false,
            'message' => 'JSON body must be an object'
        ]);
    }

    return (array) $body;
}

function extractAndValidateCouponData(array $body): array
{
    $code = $body['coupon-code'] ?? '';
    $discountType = $body['coupon-discount-type'] ?? 'percentage';

    if (!is_string($code) || !is_string($discountType)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Code and discount type must be strings'
        ]);
    }

    $code = trim($code);

    if ($code === '' || mb_strlen($code, 'UTF-8') > 50) {
        Response::json(422, [
            'success' => false,
            'message' => 'Coupon code must contain 1-50 characters'
        ]);
    }

    if (!in_array($discountType, ['percentage', 'fixed'], true)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Discount type must be percentage or fixed'
        ]);
    }

    // Match DECIMAL(10,2), rejecting invalid types and excess precision.
    $readAmount = static function ($value, string $field): string {
        if (
            (!is_int($value) && !is_float($value) && !is_string($value)) ||
            !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $value)
        ) {
            Response::json(422, [
                'success' => false,
                'message' => $field
                    . ' must be a non-negative number with at most 2 decimal places'
            ]);
        }

        return (string) $value;
    };

    $discountValue = $readAmount(
        $body['coupon-discount-value'] ?? 0,
        'Discount value'
    );

    $minOrderAmount = $readAmount(
        $body['coupon-min-order-amount'] ?? 0,
        'Minimum order amount'
    );

    if ((float) $discountValue <= 0) {
        Response::json(422, [
            'success' => false,
            'message' => 'Discount value must be greater than zero'
        ]);
    }

    if (
        $discountType === 'percentage' &&
        (float) $discountValue > 100
    ) {
        Response::json(422, [
            'success' => false,
            'message' => 'Percentage discount cannot exceed 100'
        ]);
    }

    $usageLimit = $body['coupon-usage-limit'] ?? null;

    if ($usageLimit === '') {
        $usageLimit = null;
    }

    if ($usageLimit !== null) {
        if (!is_int($usageLimit) && !is_string($usageLimit)) {
            Response::json(422, [
                'success' => false,
                'message' => 'Usage limit must be a positive integer or null'
            ]);
        }

        $usageLimit = filter_var(
            $usageLimit,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 4294967295
                ]
            ]
        );

        if ($usageLimit === false) {
            Response::json(422, [
                'success' => false,
                'message' => 'Usage limit must be a positive integer or null'
            ]);
        }
    }

    $isActive = $body['coupon-is-active'] ?? 1;

    if (!in_array($isActive, [0, 1, '0', '1', false, true], true)) {
        Response::json(422, [
            'success' => false,
            'message' => 'is_active must be 0 or 1'
        ]);
    }

    $readDate = static function ($value, string $field): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            Response::json(422, [
                'success' => false,
                'message' => $field . ' must be a date string or null'
            ]);
        }

        // Accept SQL timestamps and HTML datetime-local values.
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i',
            'Y-m-d'
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat(
                '!' . $format,
                $value
            );

            if (
                $date !== false &&
                $date->format($format) === $value &&
                (int) $date->format('Y') >= 1000
            ) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        Response::json(422, [
            'success' => false,
            'message' => 'Invalid ' . $field
        ]);
    };

    $startsAt = $readDate(
        $body['coupon-start-at'] ?? null,
        'start date'
    );

    $expiresAt = $readDate(
        $body['coupon-expires-at'] ?? null,
        'expiry date'
    );

    if (
        $startsAt !== null &&
        $expiresAt !== null &&
        $expiresAt <= $startsAt
    ) {
        Response::json(422, [
            'success' => false,
            'message' => 'Expiry date must be after start date'
        ]);
    }

    return [
        'code' => $code,
        'discount_type' => $discountType,
        'discount_value' => $discountValue,
        'usage_limit' => $usageLimit,
        'starts_at' => $startsAt,
        'expires_at' => $expiresAt,
        'min_order_amount' => $minOrderAmount,
        'is_active' => (int) $isActive
    ];
}

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Coupons connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $coupon = new Coupon($database);

    switch ($method) {
        case 'POST':
            $data = extractAndValidateCouponData(getJsonBody());

            $coupon->create($data);

            Response::json(201, [
                'success' => true,
                'message' => 'Coupon created successfully'
            ]);

        case 'PUT':
            $data = extractAndValidateCouponData(getJsonBody());

            // Lock the coupon so it cannot disappear between checking
            // its existence and updating it.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id FROM coupons WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                $pdo->rollBack();

                Response::json(404, [
                    'success' => false,
                    'message' => 'Coupon not found'
                ]);
            }

            $coupon->update($id, $data);
            $pdo->commit();

            // Submitting unchanged values is still a valid update.
            Response::json(200, [
                'success' => true,
                'message' => 'Coupon updated successfully'
            ]);

        case 'DELETE':
            $stmt = $coupon->delete($id);

            if ($stmt->rowCount() === 0) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Coupon not found'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Coupon deleted successfully'
            ]);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Coupons database error: ' . $e->getMessage());

    $errorCode = (int) ($e->errorInfo[1] ?? 0);

    if ($errorCode === 1062) {
        Response::json(409, [
            'success' => false,
            'message' => 'Coupon code already exists'
        ]);
    }

    if ($errorCode === 1451) {
        Response::json(409, [
            'success' => false,
            'message' => 'Cannot delete a coupon linked to orders'
        ]);
    }

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Coupons API error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}