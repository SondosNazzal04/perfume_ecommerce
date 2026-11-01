<?php
date_default_timezone_set('Asia/Amman');
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../core/Response.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    Response::json(403, ['success' => false, 'message' => 'Access denied: admins only']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    Response::json(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    $pdo = (new Database())->connect();
    $stats = [
        'customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
        'products' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
        'delivered_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),
        'open_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','processing','shipped')")->fetchColumn(),
        'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(pay.amount), 0) FROM payments pay JOIN orders o ON o.id = pay.order_id WHERE pay.status = 'completed' AND o.status <> 'cancelled'")->fetchColumn(),
        'low_stock' => (int) $pdo->query('SELECT COUNT(*) FROM product_variants v JOIN products p ON p.id = v.product_id WHERE p.is_active = 1 AND v.stock <= 5')->fetchColumn()
    ];

    $recent = $pdo->query(
        "SELECT o.id, o.total_amount, o.status, o.created_at,
                u.name AS customer_name, p.payment_method
         FROM orders o
         JOIN users u ON u.id = o.user_id
         LEFT JOIN payments p ON p.order_id = o.id
         ORDER BY o.created_at DESC, o.id DESC
         LIMIT 7"
    )->fetchAll(PDO::FETCH_ASSOC);

    $salesRows = $pdo->query(
        "SELECT DATE(o.created_at) AS sale_date, COALESCE(SUM(pay.amount), 0) AS revenue
         FROM orders o
         JOIN payments pay ON pay.order_id = o.id AND pay.status = 'completed'
         WHERE o.status <> 'cancelled'
           AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(o.created_at)
         ORDER BY sale_date"
    )->fetchAll(PDO::FETCH_ASSOC);

    $lowStock = $pdo->query(
        "SELECT p.id AS product_id, p.name AS product_name, b.name AS brand_name,
                v.id AS variant_id, v.size_ml, v.stock
         FROM product_variants v
         JOIN products p ON p.id = v.product_id
         JOIN brands b ON b.id = p.brand_id
         WHERE p.is_active = 1 AND v.stock <= 5
         ORDER BY v.stock ASC, p.name ASC, v.size_ml ASC
         LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);

    $salesByDate = [];
    foreach ($salesRows as $row) {
        $salesByDate[$row['sale_date']] = (float) $row['revenue'];
    }
    $sales = [];
    for ($offset = 6; $offset >= 0; $offset--) {
        $date = (new DateTimeImmutable('today'))->modify('-' . $offset . ' days')->format('Y-m-d');
        $sales[] = ['date' => $date, 'revenue' => $salesByDate[$date] ?? 0];
    }

    Response::json(200, [
        'success' => true,
        'stats' => $stats,
        'recent_orders' => $recent,
        'sales' => $sales,
        'low_stock_products' => $lowStock
    ]);
} catch (Throwable $e) {
    error_log('Admin dashboard failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to load dashboard data']);
}
