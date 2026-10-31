<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../classes/Review.php';

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

if (!in_array($method, ['GET', 'DELETE'], true)) {
    header('Allow: GET, DELETE');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

try {
    $database = new Database();
} catch (Throwable $e) {
    error_log('Admin reviews database connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $review = new Review($database);

    switch ($method) {
        case 'GET':
            $userIdFilter = isset($_GET['user_id'])
                ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT)
                : null;

            $productIdFilter = isset($_GET['product_id'])
                ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT)
                : null;

            if (
                (
                    $userIdFilter !== null &&
                    ($userIdFilter === false || $userIdFilter <= 0)
                ) ||
                (
                    $productIdFilter !== null &&
                    ($productIdFilter === false || $productIdFilter <= 0)
                )
            ) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Filters must be positive integer IDs'
                ]);
            }

            $data = $review->getAll($userIdFilter, $productIdFilter);

            Response::json(200, [
                'success' => true,
                'data' => $data
            ]);

        case 'DELETE':
            $id = isset($_GET['id'])
                ? filter_var($_GET['id'], FILTER_VALIDATE_INT)
                : null;

            if ($id === null || $id === false || $id <= 0) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'A positive review ID is required'
                ]);
            }

            if (!$review->softDelete($id)) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Review not found or already inactive'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Review soft-deleted successfully'
            ]);
    }
} catch (PDOException $e) {
    error_log('Admin reviews database error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    error_log('Admin reviews error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}