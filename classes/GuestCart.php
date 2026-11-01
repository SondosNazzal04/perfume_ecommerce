<?php

require_once __DIR__ . '/Cart.php';

final class GuestCart
{
    public static function mergeInto(PDO $pdo, int $userId): void
    {
        $guestItems = $_SESSION['guest_cart'] ?? [];
        if (!is_array($guestItems) || $guestItems === []) {
            return;
        }

        $cart = new Cart($pdo);
        $variantStmt = $pdo->prepare(
            'SELECT v.stock
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.id = ? AND p.is_active = 1'
        );
        $quantityStmt = $pdo->prepare(
            'SELECT quantity FROM cart_items WHERE user_id = ? AND variant_id = ?'
        );

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            foreach ($guestItems as $variantId => $guestQuantity) {
                $variantId = filter_var($variantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $guestQuantity = filter_var($guestQuantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($variantId === false || $guestQuantity === false) {
                    continue;
                }

                $variantStmt->execute([$variantId]);
                $stock = $variantStmt->fetchColumn();
                if ($stock === false || (int) $stock < 1) {
                    continue;
                }

                $quantityStmt->execute([$userId, $variantId]);
                $existing = (int) ($quantityStmt->fetchColumn() ?: 0);
                $quantityToMerge = min((int) $guestQuantity, max(0, (int) $stock - $existing));
                if ($quantityToMerge > 0) {
                    $cart->addItem($userId, $variantId, $quantityToMerge);
                }
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
            $_SESSION['guest_cart'] = [];
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
