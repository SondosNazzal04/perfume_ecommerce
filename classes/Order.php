<?php

class Order
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createOrder(
        $userId,
        $addressData,
        $financialData,
        $cartItems,
        $paymentMethod = 'cash',
        $couponId = null,
        $buyNow = false
    ) {
        if (!in_array($paymentMethod, ['cash', 'visa'], true)) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        if (empty($cartItems)) {
            throw new InvalidArgumentException('Your cart is empty.');
        }

        $this->pdo->beginTransaction();

        try {
            if ($buyNow) {
                $currentCart = array_map(static fn($item) => [
                    'id' => null,
                    'variant_id' => (int) $item['variant_id'],
                    'quantity' => (int) $item['quantity']
                ], $cartItems);
            } else {
                $stmt = $this->pdo->prepare(
                    "SELECT id, variant_id, quantity
                     FROM cart_items
                     WHERE user_id = ?
                     ORDER BY variant_id
                     FOR UPDATE"
                );
                $stmt->execute([$userId]);
                $currentCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (count($currentCart) !== count($cartItems)) {
                throw new RuntimeException(
                    'Your cart changed. Please refresh checkout.'
                );
            }

            $submittedItems = [];

            foreach ($cartItems as $item) {
                $variantId = (int) $item['variant_id'];

                if (isset($submittedItems[$variantId])) {
                    throw new InvalidArgumentException(
                        'Duplicate cart variant.'
                    );
                }

                $submittedItems[$variantId] = $item;
            }

            $validatedItems = [];
            $subtotalCents = 0;

            foreach ($currentCart as $cartRow) {
                $variantId = (int) $cartRow['variant_id'];
                $quantity = (int) $cartRow['quantity'];
                $submittedItem = $submittedItems[$variantId] ?? null;

                if (
                    $submittedItem === null ||
                    $quantity < 1 ||
                    (int) $submittedItem['quantity'] !== $quantity
                ) {
                    throw new RuntimeException(
                        'Your cart changed. Please refresh checkout.'
                    );
                }

                $stmt = $this->pdo->prepare(
                    "SELECT product_id FROM product_variants WHERE id = ?"
                );
                $stmt->execute([$variantId]);
                $productId = $stmt->fetchColumn();

                if ($productId === false) {
                    throw new RuntimeException('Product variant not found.');
                }

                $stmt = $this->pdo->prepare(
                    "SELECT is_active FROM products WHERE id = ? FOR UPDATE"
                );
                $stmt->execute([$productId]);
                $isActive = $stmt->fetchColumn();

                if ($isActive === false || (int) $isActive !== 1) {
                    throw new RuntimeException(
                        'A product in your cart is no longer available.'
                    );
                }

                $stmt = $this->pdo->prepare(
                    "SELECT product_id, price, discount_price, stock
                     FROM product_variants
                     WHERE id = ?
                     FOR UPDATE"
                );
                $stmt->execute([$variantId]);
                $variant = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    !$variant ||
                    (int) $variant['product_id'] !== (int) $productId
                ) {
                    throw new RuntimeException(
                        'Product variant changed. Please refresh checkout.'
                    );
                }

                if ((int) $variant['stock'] < $quantity) {
                    throw new RuntimeException(
                        'Not enough stock. Please update your cart.'
                    );
                }

                $effectivePrice = $variant['discount_price'] ?? $variant['price'];
                $priceCents = (int) round((float) $effectivePrice * 100);
                $submittedPriceCents = (int) round(
                    (float) $submittedItem['price'] * 100
                );

                if ($priceCents !== $submittedPriceCents) {
                    throw new RuntimeException(
                        'Product price changed. Please refresh checkout.'
                    );
                }

                $subtotalCents += $priceCents * $quantity;

                $validatedItems[] = [
                    'cart_id' => (int) $cartRow['id'],
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'price' => $priceCents / 100
                ];
            }

            $providedSubtotalCents = (int) round(
                (float) $financialData['subtotal'] * 100
            );

            if ($providedSubtotalCents !== $subtotalCents) {
                throw new RuntimeException(
                    'Order subtotal changed. Please refresh checkout.'
                );
            }

            if ($couponId !== null) {
                require_once __DIR__ . '/CouponService.php';
                $coupon = (new CouponService($this->pdo))->validateId(
                    (int) $couponId,
                    $subtotalCents,
                    true,
                    (int) $userId
                );
                if ((int) round((float) $financialData['discount_amount'] * 100) !== $coupon['discount_cents']) {
                    throw new RuntimeException('Coupon discount changed. Please refresh checkout.');
                }
            } elseif ((int) round((float) $financialData['discount_amount'] * 100) !== 0) {
                throw new InvalidArgumentException('A valid coupon is required for this discount.');
            }

            $deliveryCents = (int) round(
                (float) $financialData['delivery_fee'] * 100
            );
            $discountCents = (int) round(
                (float) $financialData['discount_amount'] * 100
            );
            $totalCents = (int) round(
                (float) $financialData['total_amount'] * 100
            );

            if (
                $subtotalCents <= 0 ||
                $subtotalCents > 9999999999 ||
                $deliveryCents < 0 ||
                $deliveryCents > 9999999999 ||
                $discountCents < 0 ||
                $discountCents > $subtotalCents ||
                $totalCents < 0 ||
                $totalCents > 9999999999 ||
                $totalCents !== $subtotalCents + $deliveryCents - $discountCents
            ) {
                throw new InvalidArgumentException(
                    'Invalid order financial data.'
                );
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO orders (
                    user_id, city, area, street, building,
                    subtotal, discount_amount, delivery_fee, coupon_id,
                    total_amount, status
                 ) VALUES (
                    :user_id, :city, :area, :street, :building,
                    :subtotal, :discount_amount, :delivery_fee, :coupon_id,
                    :total_amount, 'pending'
                 )"
            );
            $stmt->execute([
                'user_id' => $userId,
                'city' => $addressData['city'],
                'area' => $addressData['area'],
                'street' => $addressData['street'],
                'building' => $addressData['building'] ?? null,
                'subtotal' => $subtotalCents / 100,
                'discount_amount' => $discountCents / 100,
                'delivery_fee' => $deliveryCents / 100,
                'coupon_id' => $couponId,
                'total_amount' => $totalCents / 100
            ]);

            $orderId = (int) $this->pdo->lastInsertId();

            // Card payment must be confirmed separately.
            $stmt = $this->pdo->prepare(
                "INSERT INTO payments (order_id, amount, payment_method, status)
                 VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([
                $orderId,
                $totalCents / 100,
                $paymentMethod
            ]);

            foreach ($validatedItems as $item) {
                $stmt = $this->pdo->prepare(
                    "UPDATE product_variants
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?"
                );
                $stmt->execute([
                    $item['quantity'],
                    $item['variant_id'],
                    $item['quantity']
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Not enough stock. Please update your cart.'
                    );
                }

                $stmt = $this->pdo->prepare(
                    "INSERT INTO order_items
                        (order_id, variant_id, quantity, unit_price)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([
                    $orderId,
                    $item['variant_id'],
                    $item['quantity'],
                    $item['price']
                ]);

                if (!$buyNow) {
                    $stmt = $this->pdo->prepare(
                        "DELETE FROM cart_items WHERE id = ? AND user_id = ?"
                    );
                    $stmt->execute([$item['cart_id'], $userId]);
                }
            }

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function getOrdersByUser($userId)
    {
        $this->autoConfirmOrders();

        $stmt = $this->pdo->prepare(
            "SELECT id, total_amount, status, created_at,
                    UNIX_TIMESTAMP(DATE_ADD(created_at, INTERVAL 6 HOUR)) AS cancel_until
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCustomerOrderDetails($userId, $orderId)
    {
        $this->autoConfirmOrders();

        $stmt = $this->pdo->prepare(
            "SELECT id, city, area, street, building, subtotal,
                    discount_amount, delivery_fee, total_amount,
                    status, created_at,
                    UNIX_TIMESTAMP(DATE_ADD(created_at, INTERVAL 6 HOUR)) AS cancel_until
             FROM orders
             WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT oi.quantity, oi.unit_price AS price,
                    v.product_id,
                    p.name AS product_name, v.size_ml
             FROM order_items oi
             JOIN product_variants v ON oi.variant_id = v.id
             JOIN products p ON v.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    public function getAllOrders()
    {
        $this->autoConfirmOrders();

        $stmt = $this->pdo->query(
            "SELECT o.*, u.name AS customer_name
             FROM orders o
             JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC, o.id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderDetails($orderId)
    {
        $this->autoConfirmOrders();

        $stmt = $this->pdo->prepare(
            "SELECT o.*, u.name AS customer_name, u.email
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?"
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT oi.*, p.name AS product_name, v.size_ml
             FROM order_items oi
             JOIN product_variants v ON oi.variant_id = v.id
             JOIN products p ON v.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    public function cancelOrder($orderId, $isAdmin = false)
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "SELECT user_id, status,
                        created_at <= DATE_SUB(NOW(), INTERVAL 6 HOUR)
                            AS cancellation_expired
                 FROM orders
                 WHERE id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$order ||
                in_array($order['status'], ['cancelled', 'delivered'], true)
            ) {
                throw new RuntimeException(
                    'Order cannot be cancelled at this stage.'
                );
            }

            // Ownership is checked by the customer API before this call.
            if (
                !$isAdmin &&
                (
                    $order['status'] !== 'pending' ||
                    (int) $order['cancellation_expired'] === 1
                )
            ) {
                throw new RuntimeException(
                    'You can only cancel pending orders within 6 hours.'
                );
            }

            $stmt = $this->pdo->prepare(
                "SELECT variant_id, quantity
                 FROM order_items
                 WHERE order_id = ?
                 ORDER BY variant_id"
            );
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $stmt = $this->pdo->prepare(
                    "UPDATE product_variants
                     SET stock = stock + ?
                     WHERE id = ?"
                );
                $stmt->execute([
                    $item['quantity'],
                    $item['variant_id']
                ]);
            }

            $stmt = $this->pdo->prepare(
                "UPDATE payments
                 SET status = CASE
                     WHEN payment_method = 'visa' AND status = 'completed'
                         THEN 'refund_pending'
                     WHEN status IN ('refunded', 'refund_pending')
                         THEN status
                     ELSE 'cancelled'
                 END
                 WHERE order_id = ?"
            );
            $stmt->execute([$orderId]);

            $stmt = $this->pdo->prepare(
                "UPDATE orders SET status = 'cancelled' WHERE id = ?"
            );
            $stmt->execute([$orderId]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        if ($isAdmin) {
            $this->sendCancellationEmail(
                (int) $order['user_id'],
                (int) $orderId
            );
        }

        return true;
    }

    private function sendCancellationEmail(int $userId, int $orderId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT email FROM users WHERE id = ?"
            );
            $stmt->execute([$userId]);
            $email = $stmt->fetchColumn();

            if (!$email) {
                return;
            }

            require_once __DIR__ . '/../vendor/autoload.php';
            $config = require __DIR__ . '/../config/mail.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->Timeout = 10;
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure =
                \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $config['port'];

            $mail->setFrom($config['username'], 'Perfume Store');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Order Cancelled - Perfume Store';
            $mail->Body =
                "<p>Your order #{$orderId} has been cancelled "
                . "by the administration.</p>";
            $mail->AltBody =
                "Your order #{$orderId} has been cancelled "
                . "by the administration.";

            $mail->send();
        } catch (Throwable $e) {
            error_log('Cancellation email failed: ' . $e->getMessage());
        }
    }

    public function updateDeliveredOrderStatus(int $orderId, string $newStatus): bool
    {
        if (!in_array($newStatus, ['confirmed', 'processing', 'shipped', 'delivered'], true)) {
            throw new InvalidArgumentException('Invalid status for a delivered order.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order || $order['status'] !== 'delivered') {
                throw new RuntimeException('Only delivered orders can be edited here.');
            }

            $stmt = $this->pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    public function updateOrderStatus($orderId, $status)
    {
        if (!in_array($status, ['delivered', 'cancelled'], true)) {
            return false;
        }

        if ($status === 'cancelled') {
            return $this->cancelOrder($orderId, true);
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "SELECT status FROM orders WHERE id = ? FOR UPDATE"
            );
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $this->pdo->rollBack();
                return false;
            }

            if ($order['status'] === 'cancelled') {
                throw new RuntimeException(
                    'Cannot deliver a cancelled order.'
                );
            }

            $stmt = $this->pdo->prepare(
                "SELECT payment_method, status
                 FROM payments
                 WHERE order_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$orderId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$payments) {
                throw new RuntimeException(
                    'Order payment record is missing.'
                );
            }

            foreach ($payments as $payment) {
                $method = $payment['payment_method'];
                $paymentStatus = $payment['status'];

                $validCashPayment = $method === 'cash'
                    && in_array(
                        $paymentStatus,
                        ['pending', 'completed'],
                        true
                    );

                $validCardPayment = $method === 'visa'
                    && $paymentStatus === 'completed';

                if (!$validCashPayment && !$validCardPayment) {
                    throw new RuntimeException(
                        'Payment status does not allow order delivery.'
                    );
                }
            }

            $stmt = $this->pdo->prepare(
                "UPDATE orders SET status = 'delivered' WHERE id = ?"
            );
            $stmt->execute([$orderId]);

            $stmt = $this->pdo->prepare(
                "UPDATE payments
                 SET status = 'completed'
                 WHERE order_id = ?
                   AND payment_method = 'cash'
                   AND status = 'pending'"
            );
            $stmt->execute([$orderId]);

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function autoConfirmOrders()
    {
        $stmt = $this->pdo->prepare(
            "UPDATE orders o
             SET o.status = 'confirmed'
             WHERE o.status = 'pending'
               AND o.created_at < DATE_SUB(NOW(), INTERVAL 6 HOUR)
               AND EXISTS (
                   SELECT 1 FROM payments p
                   WHERE p.order_id = o.id
                     AND (
                         p.payment_method = 'cash'
                         OR (
                             p.payment_method = 'visa'
                             AND p.status = 'completed'
                         )
                     )
               )"
        );
        $stmt->execute();
    }
}
