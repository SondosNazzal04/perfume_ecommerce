<?php

require_once __DIR__ . '/../classes/ReviewModerator.php';
require_once __DIR__ . '/../core/Response.php';

header('Content-Type: application/json; charset=UTF-8');

// This test endpoint can call the configured AI service, so keep it local-only.
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
    Response::json(403, ['success' => false, 'message' => 'This test endpoint is available only from this computer.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    Response::json(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    Response::json(400, ['success' => false, 'message' => 'Invalid JSON body']);
}

if (!is_array($body) || !is_string($body['text'] ?? null)) {
    Response::json(422, ['success' => false, 'message' => 'A review text field is required']);
}

$text = trim($body['text']);
$characterCount = function_exists('mb_strlen')
    ? mb_strlen($text, 'UTF-8')
    : preg_match_all('/./us', $text);

if ($text === '' || $characterCount === false || $characterCount > 3000) {
    Response::json(422, ['success' => false, 'message' => 'Review text must contain 1 to 3000 characters']);
}

$configPath = __DIR__ . '/../config/ai.php';
if (!is_file($configPath)) {
    Response::json(500, ['success' => false, 'message' => 'AI config is missing. Copy config/ai.example.php to config/ai.php and add your API key.']);
}

try {
    $moderator = new ReviewModerator(require $configPath);
    $result = $moderator->analyze($text);

    Response::json(200, [
        'success' => true,
        'data' => $result,
        'notice' => 'Test only: this result was not saved and no review was published or held.'
    ]);
} catch (InvalidArgumentException $e) {
    Response::json(422, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Review moderation test failed: ' . $e->getMessage());
    Response::json(502, ['success' => false, 'message' => $e->getMessage()]);
}
