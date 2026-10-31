<?php
// File: api/ai_advisor.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/AIAdvisor.php';
require_once __DIR__ . '/../core/Response.php';

header("Content-Type: application/json; charset=UTF-8");

// 1. Connect to Database
try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Database error"]);
}

// 2. Get User Query from JSON body
$body = json_decode(file_get_contents("php://input"), true);
$userQuery = trim($body['message'] ?? '');

if (empty($userQuery)) {
    Response::json(400, ["success" => false, "message" => "Message is required"]);
}

try {
    // 3. Fetch available products from DB
    $stmt = $pdo->query("SELECT id, name, price, scent_family, description FROM products WHERE is_active = 1 LIMIT 50");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get AI Recommendations
    $aiAdvisor = new AIAdvisor();
    $recommendedIds = $aiAdvisor->getRecommendations($userQuery, $products);

    if (empty($recommendedIds)) {
        Response::json(200, [
            "success" => true, 
            "message" => "I couldn't find the perfect match. Please try different keywords.",
            "data" => []
        ]);
    }

    // 5. Fetch the full details of the recommended products
    $inQuery = implode(',', array_fill(0, count($recommendedIds), '?'));
    $stmtRecommended = $pdo->prepare("SELECT id, name, price, description FROM products WHERE id IN ($inQuery)");
    $stmtRecommended->execute($recommendedIds);
    $recommendedProducts = $stmtRecommended->fetchAll(PDO::FETCH_ASSOC);

    Response::json(200, [
        "success" => true,
        "message" => "Here are the best perfumes for you:",
        "data" => $recommendedProducts
    ]);

} catch (Throwable $e) {
    Response::json(500, ["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>
