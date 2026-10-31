<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../classes/Wishlist.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

try {
    $database = new Database();
} catch (Throwable $e) {
    error_log('Admin wishlist database connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $wishlist = new Wishlist($database);
    $data = $wishlist->getAll();

    Response::json(200, [
        'success' => true,
        'total_count' => count($data),
        'data' => $data
    ]);
} catch (PDOException $e) {
    error_log('Admin wishlist database error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    error_log('Admin wishlist error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}
