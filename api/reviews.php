<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/Review.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST', 'PUT'], true)) {
    header('Allow: GET, POST, PUT');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$userId = null;

if (in_array($method, ['POST', 'PUT'], true)) {
    if (!isset($_SESSION['user_id'])) {
        Response::json(401, [
            'success' => false,
            'message' => 'Unauthorized. Please login.'
        ]);
    }

    $userId = (int) $_SESSION['user_id'];
}

$readInteger = static function ($value, int $min, int $max): int|false {
    if (!is_int($value) && !is_string($value)) {
        return false;
    }

    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => $min, 'max_range' => $max]]
    );
};

$readJsonBody = static function (): array {
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
};

$readReviewData = static function (array $body) use ($readInteger): array {
    $rating = $readInteger($body['rating'] ?? null, 1, 5);
    $comment = $body['comment'] ?? null;

    if ($rating === false) {
        Response::json(422, [
            'success' => false,
            'message' => 'Rating must be an integer between 1 and 5'
        ]);
    }

    if ($comment !== null && !is_string($comment)) {
        Response::json(422, [
            'success' => false,
            'message' => 'Comment must be a string or null'
        ]);
    }

    if ($comment !== null) {
        $comment = trim($comment);

        if (strlen($comment) > 65535) {
            Response::json(422, [
                'success' => false,
                'message' => 'Comment exceeds the supported length'
            ]);
        }

        if ($comment === '') {
            $comment = null;
        }
    }

    return [
        'rating' => $rating,
        'comment' => $comment
    ];
};

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Reviews connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $review = new Review($database);

    switch ($method) {
        case 'GET':
            $productId = $readInteger(
                $_GET['product_id'] ?? null,
                1,
                4294967295
            );

            if ($productId === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid product_id is required'
                ]);
            }

            $data = $review->getByProductId($productId);

            Response::json(200, [
                'success' => true,
                'data' => $data
            ]);

        case 'POST':
            $body = $readJsonBody();
            $data = $readReviewData($body);

            $orderId = $readInteger(
                $body['order_id'] ?? null,
                1,
                4294967295
            );

            $productId = $readInteger(
                $body['product_id'] ?? null,
                1,
                4294967295
            );

            if ($orderId === false || $productId === false) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Valid order_id and product_id are required'
                ]);
            }

            if (!$review->verifyOrderOwnership($userId, $orderId, $productId)) {
                Response::json(403, [
                    'success' => false,
                    'message' => 'You can only review products you have received.'
                ]);
            }

            $review->create([
                'user_id' => $userId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'rating' => $data['rating'],
                'comment' => $data['comment']
            ]);

            Response::json(201, [
                'success' => true,
                'message' => 'Review submitted successfully'
            ]);

        case 'PUT':
            $id = $readInteger(
                $_GET['id'] ?? null,
                1,
                4294967295
            );

            if ($id === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Valid review ID is required'
                ]);
            }

            $body = $readJsonBody();
            $data = $readReviewData($body);

            // Lock the owned review while checking and updating it.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id, is_active
                 FROM reviews
                 WHERE id = ? AND user_id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$id, $userId]);
            $existingReview = $stmt->fetch();

            if (!$existingReview) {
                $pdo->rollBack();

                Response::json(404, [
                    'success' => false,
                    'message' => 'Review not found'
                ]);
            }

            if ((int) $existingReview['is_active'] !== 1) {
                $pdo->rollBack();

                Response::json(403, [
                    'success' => false,
                    'message' => 'This review is inactive and cannot be edited'
                ]);
            }

            // An unchanged rating/comment is a valid update.
            $review->update(
                $id,
                $userId,
                $data['rating'],
                $data['comment']
            );

            $pdo->commit();

            Response::json(200, [
                'success' => true,
                'message' => 'Review updated successfully'
            ]);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Reviews database error: ' . $e->getMessage());

    $driverCode = (int) ($e->errorInfo[1] ?? 0);

    if ($driverCode === 1062) {
        Response::json(409, [
            'success' => false,
            'message' => 'You have already reviewed this product.'
        ]);
    }

    if ($driverCode === 1452) {
        Response::json(409, [
            'success' => false,
            'message' => 'The referenced user, product or order no longer exists'
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

    error_log('Reviews API error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}