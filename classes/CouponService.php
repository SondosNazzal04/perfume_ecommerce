<?php

final class CouponService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function validateCode(string $code, int $subtotalCents, ?int $userId = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, discount_type, discount_value, min_order_amount,
                    usage_limit, starts_at, expires_at, is_active
             FROM coupons
             WHERE UPPER(code) = UPPER(?)
             LIMIT 1'
        );
        $stmt->execute([trim($code)]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->validateCoupon($coupon ?: null, $subtotalCents, $userId);
    }

    public function validateId(int $couponId, int $subtotalCents, bool $lock = false, ?int $userId = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, discount_type, discount_value, min_order_amount,
                    usage_limit, starts_at, expires_at, is_active
             FROM coupons
             WHERE id = ?' . ($lock ? ' FOR UPDATE' : '')
        );
        $stmt->execute([$couponId]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->validateCoupon($coupon ?: null, $subtotalCents, $userId);
    }

    private function validateCoupon(?array $coupon, int $subtotalCents, ?int $userId): array
    {
        if ($coupon === null || (int) $coupon['is_active'] !== 1) {
            throw new DomainException('This coupon code is not valid.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Amman'));
        if (
            !empty($coupon['starts_at']) &&
            new DateTimeImmutable($coupon['starts_at'], new DateTimeZone('Asia/Amman')) > $now
        ) {
            throw new DomainException('This coupon is not active yet.');
        }
        if (
            !empty($coupon['expires_at']) &&
            new DateTimeImmutable($coupon['expires_at'], new DateTimeZone('Asia/Amman')) < $now
        ) {
            throw new DomainException('This coupon has expired.');
        }

        $minimumCents = (int) round((float) $coupon['min_order_amount'] * 100);
        if ($subtotalCents < $minimumCents) {
            $minimum = number_format($minimumCents / 100, 3);
            throw new DomainException('This coupon requires a minimum order of ' . $minimum . ' JOD.');
        }

        if ($coupon['usage_limit'] !== null) {
            $usageStmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM orders
                 WHERE coupon_id = ? AND status <> 'cancelled'"
            );
            $usageStmt->execute([(int) $coupon['id']]);
            if ((int) $usageStmt->fetchColumn() >= (int) $coupon['usage_limit']) {
                throw new DomainException('This coupon has reached its usage limit.');
            }
        }

        if ($userId !== null) {
            $userUsageStmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM orders
                 WHERE coupon_id = ?
                   AND user_id = ?
                   AND status <> 'cancelled'"
            );
            $userUsageStmt->execute([(int) $coupon['id'], $userId]);
            if ((int) $userUsageStmt->fetchColumn() > 0) {
                throw new DomainException('You have already used this coupon.');
            }
        }

        $value = (float) $coupon['discount_value'];
        $discountCents = $coupon['discount_type'] === 'percentage'
            ? (int) round($subtotalCents * $value / 100)
            : (int) round($value * 100);
        $discountCents = min($subtotalCents, max(0, $discountCents));

        return [
            'coupon_id' => (int) $coupon['id'],
            'code' => (string) $coupon['code'],
            'discount_cents' => $discountCents,
            'discount_amount' => $discountCents / 100,
            'discount_type' => (string) $coupon['discount_type'],
            'discount_value' => $value
        ];
    }
}
