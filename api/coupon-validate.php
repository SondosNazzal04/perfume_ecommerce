<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CouponService.php';
require_once __DIR__ . '/../core/Response.php';

date_default_timezone_set('Asia/Amman');
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
    header('Allow: GET, POST, DELETE');
    Response::json(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    $pdo = (new Database())->connect();
    $cartItems = [];
    if (isset($_SESSION['user_id'])) {
        $cartItems = (new Cart($pdo))->getCartItems((int) $_SESSION['user_id']);
    } else {
        $guestCart = $_SESSION['guest_cart'] ?? [];
        if (is_array($guestCart) && $guestCart !== []) {
            $stmt = $pdo->prepare(
                'SELECT ? AS quantity, COALESCE(v.discount_price, v.price) AS price
                 FROM product_variants v
                 JOIN products p ON p.id = v.product_id
                 WHERE v.id = ? AND p.is_active = 1'
            );
            foreach ($guestCart as $variantId => $quantity) {
                $variantId = filter_var($variantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $quantity = filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($variantId === false || $quantity === false) {
                    continue;
                }
                $stmt->execute([$quantity, $variantId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($item) {
                    $cartItems[] = $item;
                }
            }
        }
    }

    $subtotalCents = 0;
    foreach ($cartItems as $item) {
        $subtotalCents += (int) round((float) $item['price'] * 100) * (int) $item['quantity'];
    }
    if ($method === 'GET' && isset($_GET['subtotal'])) {
        $previewSubtotal = filter_var($_GET['subtotal'], FILTER_VALIDATE_FLOAT);
        if ($previewSubtotal === false || $previewSubtotal < 0 || $previewSubtotal > 99999999) {
            Response::json(422, ['success' => false, 'message' => 'Invalid order subtotal.']);
        }
        $subtotalCents = (int) round($previewSubtotal * 100);
    }

    if ($method === 'DELETE') {
        unset($_SESSION['applied_coupon']);
        Response::json(200, [
            'success' => true,
            'applied' => false,
            'code' => null,
            'discount_amount' => 0,
            'subtotal' => $subtotalCents / 100
        ]);
    }

    $code = '';
    if ($method === 'POST') {
        try {
            $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            Response::json(400, ['success' => false, 'message' => 'Invalid JSON body']);
        }
        $code = is_array($body) && is_string($body['code'] ?? null)
            ? trim($body['code'])
            : '';
    } else {
        $code = is_string($_SESSION['applied_coupon'] ?? null)
            ? $_SESSION['applied_coupon']
            : '';
    }

    if ($code === '') {
        if ($method === 'POST') {
            unset($_SESSION['applied_coupon']);
        }
        Response::json(200, [
            'success' => true,
            'applied' => false,
            'code' => null,
            'discount_amount' => 0,
            'subtotal' => $subtotalCents / 100
        ]);
    }
    if (mb_strlen($code, 'UTF-8') > 50) {
        Response::json(422, ['success' => false, 'message' => 'Coupon code is too long.']);
    }
    if ($subtotalCents <= 0) {
        Response::json(422, ['success' => false, 'message' => 'Add an item to your bag before applying a coupon.']);
    }

    try {
        $coupon = (new CouponService($pdo))->validateCode(
            $code,
            $subtotalCents,
            isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null
        );
    } catch (DomainException $e) {
        if ($method === 'POST') {
            unset($_SESSION['applied_coupon']);
        }
        if ($method === 'GET') {
            unset($_SESSION['applied_coupon']);
            Response::json(200, [
                'success' => true,
                'applied' => false,
                'code' => null,
                'discount_amount' => 0,
                'subtotal' => $subtotalCents / 100,
                'message' => $e->getMessage()
            ]);
        }
        Response::json(422, ['success' => false, 'message' => $e->getMessage()]);
    }

    $_SESSION['applied_coupon'] = $coupon['code'];
    Response::json(200, [
        'success' => true,
        'applied' => true,
        'code' => $coupon['code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'discount_amount' => $coupon['discount_amount'],
        'subtotal' => $subtotalCents / 100
    ]);
} catch (Throwable $e) {
    error_log('Coupon validation failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to apply this coupon right now.']);
}
