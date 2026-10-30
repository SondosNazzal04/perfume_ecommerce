<?php

require_once __DIR__ . '/../includes/init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}



if (!isset($_SESSION['user_id'])) {
    Response::json(401, [
        'success' => false,
        'message' => 'Unauthorized'
    ]);
}

$user = $userModel->findById((int) $_SESSION['user_id']);

if ($user === null) {
    $_SESSION = [];
    session_destroy();

    Response::json(401, [
        'success' => false,
        'message' => 'Unauthorized'
    ]);
}

Response::json(200, [
    'success' => true,
    'user' => $user
]);