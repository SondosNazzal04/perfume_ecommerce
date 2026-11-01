<?php
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

$search = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;

try {
    $pdo = (new Database())->connect();
    $where = "role = 'customer'";
    $params = [];
    if ($search !== '') {
        $where .= ' AND (name LIKE ? OR email LIKE ?)';
        $params = ['%' . $search . '%', '%' . $search . '%'];
    }
    $count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / $limit));
    $page = min($page, $pages);
    $stmt = $pdo->prepare(
        "SELECT id, name, email, phone, email_verified_at, created_at
         FROM users WHERE $where
         ORDER BY created_at DESC, id DESC
         LIMIT $limit OFFSET " . (($page - 1) * $limit)
    );
    $stmt->execute($params);
    Response::json(200, [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pagination' => ['total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $limit]
    ]);
} catch (Throwable $e) {
    error_log('Admin customer list failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to load customers']);
}
