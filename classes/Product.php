<?php

class Product
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $sql = "SELECT
                    p.id,
                    p.brand_id,
                    b.name AS brand_name,
                    p.name,
                    p.slug,
                    p.description,
                    p.is_active,
                    p.created_at,
                    p.updated_at
                FROM products p
                JOIN brands b ON b.id = p.brand_id
                ORDER BY p.id DESC";

        $stmt = $this->pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $productIndexes = [];

        foreach ($products as $index => $product) {
            $products[$index]['category_ids'] = [];
            $productIndexes[(int) $product['id']] = $index;
        }

        $categoryStmt = $this->pdo->query(
            "SELECT product_id, category_id FROM product_categories"
        );

        foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $productId = (int) $row['product_id'];

            if (isset($productIndexes[$productId])) {
                $index = $productIndexes[$productId];
                $products[$index]['category_ids'][] = (int) $row['category_id'];
            }
        }

        return $products;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    p.id,
                    p.brand_id,
                    b.name AS brand_name,
                    p.name,
                    p.slug,
                    p.description,
                    p.is_active,
                    p.created_at,
                    p.updated_at
                FROM products p
                JOIN brands b ON b.id = p.brand_id
                WHERE p.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            return null;
        }

        $categoryStmt = $this->pdo->prepare(
            "SELECT category_id
             FROM product_categories
             WHERE product_id = :product_id"
        );

        $categoryStmt->execute([
            ':product_id' => $id
        ]);

        $product['category_ids'] = array_map(
            'intval',
            $categoryStmt->fetchAll(PDO::FETCH_COLUMN)
        );

        return $product;
    }

    public function addProduct(
        int $brandId,
        string $name,
        string $slug,
        ?string $description = null,
        bool $isActive = true,
        array $categoryIds = []
    ): int {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        $this->pdo->beginTransaction();

        try {
            $sql = "INSERT INTO products
                        (brand_id, name, slug, description, is_active)
                    VALUES
                        (:brand_id, :name, :slug, :description, :is_active)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':brand_id' => $brandId,
                ':name' => trim($name),
                ':slug' => trim($slug),
                ':description' => $description,
                ':is_active' => $isActive ? 1 : 0
            ]);

            $productId = (int) $this->pdo->lastInsertId();

            $this->syncCategories($productId, $categoryIds);

            $this->pdo->commit();

            return $productId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function updateProduct(
        int $id,
        int $brandId,
        string $name,
        string $slug,
        ?string $description = null,
        bool $isActive = true,
        array $categoryIds = []
    ): bool {
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        $this->pdo->beginTransaction();

        try {
            $sql = "UPDATE products
                    SET brand_id = :brand_id,
                        name = :name,
                        slug = :slug,
                        description = :description,
                        is_active = :is_active
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':brand_id' => $brandId,
                ':name' => trim($name),
                ':slug' => trim($slug),
                ':description' => $description,
                ':is_active' => $isActive ? 1 : 0
            ]);

            $this->syncCategories($id, $categoryIds);

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return array{deleted: bool, archived: bool, reason: string|null}
     */
    public function deleteProduct(int $id): array
    {
        $this->pdo->beginTransaction();

        try {
            $lockStmt = $this->pdo->prepare(
                "SELECT id FROM products WHERE id = :id FOR UPDATE"
            );
            $lockStmt->execute([':id' => $id]);

            if (!$lockStmt->fetchColumn()) {
                $this->pdo->commit();

                return [
                    'deleted' => false,
                    'archived' => false,
                    'reason' => 'not_found'
                ];
            }

            $orderStmt = $this->pdo->prepare(
                "SELECT 1
                 FROM order_items oi
                 JOIN product_variants pv ON pv.id = oi.variant_id
                 WHERE pv.product_id = :id
                 LIMIT 1"
            );
            $orderStmt->execute([':id' => $id]);
            $hasOrders = $orderStmt->fetchColumn() !== false;

            $reviewStmt = $this->pdo->prepare(
                "SELECT 1 FROM reviews WHERE product_id = :id LIMIT 1"
            );
            $reviewStmt->execute([':id' => $id]);
            $hasReviews = $reviewStmt->fetchColumn() !== false;

            if ($hasOrders || $hasReviews) {
                $archiveStmt = $this->pdo->prepare(
                    "UPDATE products SET is_active = 0 WHERE id = :id"
                );
                $archiveStmt->execute([':id' => $id]);

                // Remove the archived product's variants from active carts.
                $cartStmt = $this->pdo->prepare(
                    "DELETE ci
                     FROM cart_items ci
                     JOIN product_variants pv ON pv.id = ci.variant_id
                     WHERE pv.product_id = :id"
                );
                $cartStmt->execute([':id' => $id]);

                $this->pdo->commit();

                return [
                    'deleted' => false,
                    'archived' => true,
                    'reason' => $hasOrders ? 'order_history' : 'reviews'
                ];
            }

            // These related records are disposable when there is no sales or review history.
            $imageStmt = $this->pdo->prepare(
                "SELECT image_path FROM product_images WHERE product_id = :id"
            );
            $imageStmt->execute([':id' => $id]);
            $imagePaths = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

            $relatedDeletes = [
                "DELETE ci FROM cart_items ci
                 JOIN product_variants pv ON pv.id = ci.variant_id
                 WHERE pv.product_id = :id",
                "DELETE FROM wishlist WHERE product_id = :id",
                "DELETE FROM ai_recommendation_items WHERE product_id = :id",
                "DELETE FROM product_images WHERE product_id = :id",
                "DELETE FROM product_categories WHERE product_id = :id",
                "DELETE FROM product_variants WHERE product_id = :id"
            ];

            foreach ($relatedDeletes as $sql) {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
            }

            $productStmt = $this->pdo->prepare(
                "DELETE FROM products WHERE id = :id"
            );
            $productStmt->execute([':id' => $id]);

            $deleted = $productStmt->rowCount() > 0;
            $this->pdo->commit();

            foreach ($imagePaths as $imagePath) {
                $this->deleteLocalProductImage((string) $imagePath);
            }

            return [
                'deleted' => $deleted,
                'archived' => false,
                'reason' => null
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function deleteLocalProductImage(string $imagePath): void
    {
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return;
        }

        $fileName = basename(str_replace('\\', '/', $imagePath));

        // Only remove random filenames generated for uploads/products.
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/i', $fileName)) {
            return;
        }

        $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($filePath) && !unlink($filePath)) {
            error_log('Could not remove product image: ' . $filePath);
        }
    }

    private function syncCategories(int $productId, array $categoryIds): void
    {
        $deleteStmt = $this->pdo->prepare(
            "DELETE FROM product_categories WHERE product_id = :product_id"
        );

        $deleteStmt->execute([
            ':product_id' => $productId
        ]);

        if (count($categoryIds) === 0) {
            return;
        }

        $insertStmt = $this->pdo->prepare(
            "INSERT INTO product_categories (product_id, category_id)
             VALUES (:product_id, :category_id)"
        );

        foreach ($categoryIds as $categoryId) {
            $insertStmt->execute([
                ':product_id' => $productId,
                ':category_id' => $categoryId
            ]);
        }
    }

    private function normalizeCategoryIds(array $categoryIds): array
    {
        $normalizedIds = [];

        foreach ($categoryIds as $categoryId) {
            $validId = filter_var(
                $categoryId,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($validId === false) {
                throw new InvalidArgumentException(
                    'Category IDs must be positive integers.'
                );
            }

            $normalizedIds[] = (int) $validId;
        }

        return array_values(array_unique($normalizedIds));
    }
}
