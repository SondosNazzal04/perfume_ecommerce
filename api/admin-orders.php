<?php


require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

session_start();
// Assuming your login system stores role in $_SESSION['role']
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    Response::json(403, ["success" => false, "message" => "Access Denied: Admins only"]);
    exit;
}

// Connect to Database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

$method = $_SERVER["REQUEST_METHOD"];
$id = isset($_GET["id"]) ? filter_var($_GET["id"], FILTER_VALIDATE_INT) : null;

// Helper function to read JSON body
function getJsonBody(): array {
    $rawBody = file_get_contents("php://input");
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::json(400, ["success" => false, "message" => "Invalid JSON body"]);
    }
    return is_array($data) ? $data : [];
}

try {
    $orderModel = new Order($pdo);

    switch ($method) {
        // GET: Retrieve all orders (With Search, Filter, Pagination) OR a single order
        case "GET":
            // 1. If requesting a single order details
            if ($id !== null) {
                $order = $orderModel->getOrderDetails($id);
                if ($order) {
                    Response::json(200, ["success" => true, "data" => $order]);
                } else {
                    Response::json(404, ["success" => false, "message" => "Order not found"]);
                }
                break;
            }

            // 2. Otherwise, get list with filters and pagination
            $orderModel->autoConfirmOrders(); // Auto-confirm 6-hour pending orders

            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10; // Number of orders per page
            $offset = ($page - 1) * $limit;

            // Build base query
            $query = "FROM orders o 
            JOIN users u ON o.user_id = u.id 
            LEFT JOIN payments p ON o.id = p.order_id 
            WHERE 1=1";
            $params = [];

            // Add Search condition (by Customer Name or Order ID)
            if (!empty($search)) {
                $query .= " AND (u.name LIKE :search OR o.id = :order_id)";
                $params[':search'] = "%$search%";
                $params[':order_id'] = is_numeric($search) ? $search : 0;
            }

            // Add Status Filter condition
            if (!empty($status)) {
                $query .= " AND o.status = :status";
                $params[':status'] = $status;
            }

            // Get Total Count (for Pagination)
            $countStmt = $pdo->prepare("SELECT COUNT(*) " . $query);
            $countStmt->execute($params);
            $totalOrders = $countStmt->fetchColumn();
            $totalPages = ceil($totalOrders / $limit);

            // Get Paginated Results
            $sql = "SELECT o.*, u.name as customer_name, p.payment_method " . $query . " ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            Response::json(200, [
                "success" => true,
                "data" => $orders,
                "pagination" => [
                    "total_orders" => $totalOrders,
                    "total_pages" => $totalPages,
                    "current_page" => $page
                ]
            ]);
            break;

        // PUT: Update order status
        case "PUT":
            if ($id === null) {
                Response::json(400, ["success" => false, "message" => "Order ID is required"]);
            }
            $body = getJsonBody();
            $newStatus = trim($body["status"] ?? "");

            if (empty($newStatus)) {
                Response::json(422, ["success" => false, "message" => "Status is required"]);
            }

            try {
                // Order model handles updating status, cancel logic, and stock refund
                if ($orderModel->updateOrderStatus($id, $newStatus)) {
                    Response::json(200, ["success" => true, "message" => "Order status updated to: " . $newStatus]);
                } else {
                    Response::json(400, ["success" => false, "message" => "Failed to update status. Ensure status is valid."]);
                }
            } catch (Exception $e) {
                Response::json(400, ["success" => false, "message" => $e->getMessage()]);
            }
            break;

        default:
            header("Allow: GET, PUT");
            Response::json(405, ["success" => false, "message" => "Method not allowed"]);
    }
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>