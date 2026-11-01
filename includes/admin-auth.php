<?php
require_once __DIR__ . '/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?return=admin/index.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied: admins only.');
}
