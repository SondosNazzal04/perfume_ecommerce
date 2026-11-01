<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../core/Response.php';

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

if (!in_array($method, ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$id = isset($_GET['id'])
    ? filter_var($_GET['id'], FILTER_VALIDATE_INT)
    : null;

if ($id !== null && ($id === false || $id <= 0)) {
    Response::json(400, [
        'success' => false,
        'message' => 'Order ID must be a positive integer'
    ]);
}

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Admin orders connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $orderModel = new Order($pdo);

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                $order = $orderModel->getOrderDetails($id);

                if (!$order) {
                    Response::json(404, [
                        'success' => false,
                        'message' => 'Order not found'
                    ]);
                }

                Response::json(200, [
                    'success' => true,
                    'data' => $order
                ]);
            }

            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';

            if (!is_string($search) || !is_string($status)) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Search and status must be strings'
                ]);
            }

            $search = trim($search);
            $status = trim($status);

            $allowedStatuses = [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled'
            ];

            if (
                $status !== '' &&
                !in_array($status, $allowedStatuses, true)
            ) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Invalid order status'
                ]);
            }

            $page = isset($_GET['page'])
                ? filter_var(
                    $_GET['page'],
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                            'max_range' => intdiv(PHP_INT_MAX, 10)
                        ]
                    ]
                )
                : 1;

            if ($page === false) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Invalid page number'
                ]);
            }

            $limit = 10;
            $offset = ($page - 1) * $limit;

            $orderModel->autoConfirmOrders();

            $query = "
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE 1 = 1
            ";

            $params = [];

            if ($search !== '') {
                $searchOrderId = filter_var(
                    $search,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                $query .= " AND (
                    u.name LIKE :search
                    OR o.id = :order_id
                )";

                $params['search'] = '%' . $search . '%';
                $params['order_id'] = $searchOrderId !== false
                    ? $searchOrderId
                    : 0;
            }

            if ($status !== '') {
                $query .= ' AND o.status = :status';
                $params['status'] = $status;
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) ' . $query);
            $countStmt->execute($params);

            $totalOrders = (int) $countStmt->fetchColumn();
            $totalPages = (int) ceil($totalOrders / $limit);

            $sql = "
                SELECT
                    o.*,
                    u.name AS customer_name,
                    p.payment_method
                " . $query . "
                ORDER BY o.created_at DESC, o.id DESC
                LIMIT $limit OFFSET $offset
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            Response::json(200, [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'pagination' => [
                    'total_orders' => $totalOrders,
                    'total_pages' => $totalPages,
                    'current_page' => $page
                ]
            ]);

        case 'PUT':
            if ($id === null) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Order ID is required'
                ]);
            }

            try {
                $body = json_decode(
                    file_get_contents('php://input'),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (JsonException $e) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Invalid JSON body'
                ]);
            }

            if (!is_array($body)) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Invalid JSON body'
                ]);
            }

            if (($body['action'] ?? '') === 'edit_delivered_status') {
                $newStatus = $body['status'] ?? '';
                if (!is_string($newStatus) || !in_array(trim($newStatus), ['confirmed', 'processing', 'shipped', 'delivered'], true)) {
                    Response::json(422, ['success' => false, 'message' => 'Invalid status for a delivered order']);
                }
                $order = $orderModel->getOrderDetails($id);
                if (!$order) {
                    Response::json(404, ['success' => false, 'message' => 'Order not found']);
                }
                if ($order['status'] !== 'delivered') {
                    Response::json(409, ['success' => false, 'message' => 'Only delivered orders can be edited here']);
                }
                try {
                    $orderModel->updateDeliveredOrderStatus((int) $id, trim($newStatus));
                } catch (InvalidArgumentException $e) {
                    Response::json(422, ['success' => false, 'message' => $e->getMessage()]);
                } catch (RuntimeException $e) {
                    Response::json(409, ['success' => false, 'message' => $e->getMessage()]);
                }
                Response::json(200, ['success' => true, 'message' => 'Order status updated', 'data' => ['status' => trim($newStatus)]]);
            }
            $newStatus = $body['status'] ?? '';

            if (!is_string($newStatus)) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Status must be a string'
                ]);
            }

            $newStatus = trim($newStatus);

            // Matches the current Order::updateOrderStatus() implementation.
            if (!in_array($newStatus, ['delivered', 'cancelled'], true)) {
                Response::json(422, [
                    'success' => false,
                    'message' => 'Status must be delivered or cancelled'
                ]);
            }

            $order = $orderModel->getOrderDetails($id);

            if (!$order) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }

            try {
                $updated = $orderModel->updateOrderStatus($id, $newStatus);
            } catch (PDOException $e) {
                throw $e;
            } catch (Exception $e) {
                error_log('Order status update rejected: ' . $e->getMessage());

                Response::json(400, [
                    'success' => false,
                    'message' => 'Unable to change the order status at this stage'
                ]);
            }

            if (!$updated) {
                Response::json(400, [
                    'success' => false,
                    'message' => 'Failed to update order status'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Order status updated to: ' . $newStatus
            ]);
    }
} catch (Throwable $e) {
    error_log('Admin orders failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}