<?php

class ProductVariant
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByProductId(int $productId): array
    {
        $sql = "SELECT
                    id,
                    product_id,
                    size_ml,
                    price,
                    discount_price,
                    stock,
                    created_at,
                    updated_at
                FROM product_variants
                WHERE product_id = :product_id
                ORDER BY size_ml ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':product_id' => $productId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    id,
                    product_id,
                    size_ml,
                    price,
                    discount_price,
                    stock,
                    created_at,
                    updated_at
                FROM product_variants
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        return $variant ?: null;
    }

    public function addVariant(
        int $productId,
        int $sizeMl,
        float $price,
        ?float $discountPrice = null,
        int $stock = 0
    ): int {
        $this->validateVariantData($sizeMl, $price, $discountPrice, $stock);

        $sql = "INSERT INTO product_variants
                    (product_id, size_ml, price, discount_price, stock)
                VALUES
                    (:product_id, :size_ml, :price, :discount_price, :stock)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':product_id' => $productId,
            ':size_ml' => $sizeMl,
            ':price' => $price,
            ':discount_price' => $discountPrice,
            ':stock' => $stock
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateVariant(
        int $id,
        int $productId,
        int $sizeMl,
        float $price,
        ?float $discountPrice = null,
        int $stock = 0
    ): bool {
        $this->validateVariantData($sizeMl, $price, $discountPrice, $stock);

        $sql = "UPDATE product_variants
                SET product_id = :product_id,
                    size_ml = :size_ml,
                    price = :price,
                    discount_price = :discount_price,
                    stock = :stock
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':product_id' => $productId,
            ':size_ml' => $sizeMl,
            ':price' => $price,
            ':discount_price' => $discountPrice,
            ':stock' => $stock
        ]);
    }

    public function deleteVariant(int $id): bool
    {
        $sql = "DELETE FROM product_variants
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    private function validateVariantData(
        int $sizeMl,
        float $price,
        ?float $discountPrice,
        int $stock
    ): void {
        if ($sizeMl < 1 || $sizeMl > 65535) {
            throw new InvalidArgumentException(
                'Size must be between 1 and 65535 ml.'
            );
        }

        if ($price <= 0) {
            throw new InvalidArgumentException(
                'Price must be greater than zero.'
            );
        }

        if (
            $discountPrice !== null
            && ($discountPrice <= 0 || $discountPrice >= $price)
        ) {
            throw new InvalidArgumentException(
                'Discount price must be greater than zero and lower than price.'
            );
        }

        if ($stock < 0) {
            throw new InvalidArgumentException(
                'Stock cannot be negative.'
            );
        }
    }
}
