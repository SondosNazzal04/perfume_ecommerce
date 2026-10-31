<?php

class ProductImage
{
    private $pdo;
    private string $uploadDirectory;
    private string $relativeDirectory = 'uploads/products';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->uploadDirectory =
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'products';
    }

    public function getByProductId(int $productId): array
    {
        $sql = "SELECT id, product_id, image_path, is_primary, sort_order, created_at
                FROM product_images
                WHERE product_id = :product_id
                ORDER BY is_primary DESC, sort_order ASC, id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':product_id' => $productId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, product_id, image_path, is_primary, sort_order, created_at
                FROM product_images
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        return $image ?: null;
    }

    public function addImage(
        int $productId,
        array $uploadedFile,
        bool $isPrimary = false,
        int $sortOrder = 0
    ): int {
        if ($sortOrder < 0) {
            throw new InvalidArgumentException('Sort order cannot be negative.');
        }

        $extension = $this->validateUploadedFile($uploadedFile);
        $this->ensureUploadDirectory();

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $this->uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
        $relativePath = $this->relativeDirectory . '/' . $fileName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Could not save the uploaded image.');
        }

        try {
            $this->pdo->beginTransaction();

            $productStmt = $this->pdo->prepare(
                "SELECT id FROM products WHERE id = :id FOR UPDATE"
            );
            $productStmt->execute([':id' => $productId]);

            if (!$productStmt->fetchColumn()) {
                throw new InvalidArgumentException('Product not found.');
            }

            $countStmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM product_images WHERE product_id = :product_id"
            );
            $countStmt->execute([':product_id' => $productId]);
            $isFirstImage = (int) $countStmt->fetchColumn() === 0;

            $makePrimary = $isPrimary || $isFirstImage;

            if ($makePrimary) {
                $clearPrimaryStmt = $this->pdo->prepare(
                    "UPDATE product_images
                     SET is_primary = 0
                     WHERE product_id = :product_id"
                );
                $clearPrimaryStmt->execute([':product_id' => $productId]);
            }

            $insertStmt = $this->pdo->prepare(
                "INSERT INTO product_images
                    (product_id, image_path, is_primary, sort_order)
                 VALUES
                    (:product_id, :image_path, :is_primary, :sort_order)"
            );

            $insertStmt->execute([
                ':product_id' => $productId,
                ':image_path' => $relativePath,
                ':is_primary' => $makePrimary ? 1 : 0,
                ':sort_order' => $sortOrder
            ]);

            $imageId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $imageId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }

            throw $e;
        }
    }

    public function addImageUrl(
        int $productId,
        string $imageUrl,
        bool $isPrimary = false,
        int $sortOrder = 0
    ): int {
        $imageUrl = trim($imageUrl);
        $urlParts = filter_var($imageUrl, FILTER_VALIDATE_URL);
        $scheme = strtolower((string) parse_url($imageUrl, PHP_URL_SCHEME));

        if ($urlParts === false || !in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Image URL must be a valid HTTP or HTTPS URL.');
        }

        if (strlen($imageUrl) > 2048) {
            throw new InvalidArgumentException('Image URL cannot be longer than 2048 characters.');
        }

        if ($sortOrder < 0) {
            throw new InvalidArgumentException('Sort order cannot be negative.');
        }

        $this->pdo->beginTransaction();

        try {
            $productStmt = $this->pdo->prepare(
                "SELECT id FROM products WHERE id = :id FOR UPDATE"
            );
            $productStmt->execute([':id' => $productId]);

            if (!$productStmt->fetchColumn()) {
                throw new InvalidArgumentException('Product not found.');
            }

            $countStmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM product_images WHERE product_id = :product_id"
            );
            $countStmt->execute([':product_id' => $productId]);
            $makePrimary = $isPrimary || (int) $countStmt->fetchColumn() === 0;

            if ($makePrimary) {
                $clearStmt = $this->pdo->prepare(
                    "UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id"
                );
                $clearStmt->execute([':product_id' => $productId]);
            }

            $insertStmt = $this->pdo->prepare(
                "INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
                 VALUES (:product_id, :image_path, :is_primary, :sort_order)"
            );
            $insertStmt->execute([
                ':product_id' => $productId,
                ':image_path' => $imageUrl,
                ':is_primary' => $makePrimary ? 1 : 0,
                ':sort_order' => $sortOrder
            ]);

            $imageId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $imageId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function setPrimary(int $id): bool
    {
        $this->pdo->beginTransaction();

        try {
            $findStmt = $this->pdo->prepare(
                "SELECT product_id FROM product_images WHERE id = :id FOR UPDATE"
            );
            $productStmt = $this->pdo->prepare(
                "SELECT product_id FROM product_images WHERE id = ?"
            );
            $productStmt->execute([$id]);
            $parentProductId = $productStmt->fetchColumn();

            if ($parentProductId === false) {
                $this->pdo->rollBack();
                return false;
            }

            $productStmt = $this->pdo->prepare(
                "SELECT id FROM products WHERE id = ? FOR UPDATE"
            );
            $productStmt->execute([$parentProductId]);

            if ($productStmt->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }
            $findStmt->execute([':id' => $id]);
            $productId = $findStmt->fetchColumn();

            if ($productId === false) {
                $this->pdo->commit();
                return false;
            }

            $clearStmt = $this->pdo->prepare(
                "UPDATE product_images
                 SET is_primary = 0
                 WHERE product_id = :product_id"
            );
            $clearStmt->execute([':product_id' => $productId]);

            $setStmt = $this->pdo->prepare(
                "UPDATE product_images
                 SET is_primary = 1
                 WHERE id = :id"
            );
            $setStmt->execute([':id' => $id]);

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function deleteImage(int $id): bool
    {
        $this->pdo->beginTransaction();

        try {
            $findStmt = $this->pdo->prepare(
                "SELECT product_id, image_path, is_primary
                 FROM product_images
                 WHERE id = :id
                 FOR UPDATE"
            );
            $productStmt = $this->pdo->prepare(
                "SELECT product_id FROM product_images WHERE id = ?"
            );
            $productStmt->execute([$id]);
            $parentProductId = $productStmt->fetchColumn();

            if ($parentProductId === false) {
                $this->pdo->rollBack();
                return false;
            }

            $productStmt = $this->pdo->prepare(
                "SELECT id FROM products WHERE id = ? FOR UPDATE"
            );
            $productStmt->execute([$parentProductId]);

            if ($productStmt->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }
            $findStmt->execute([':id' => $id]);
            $image = $findStmt->fetch(PDO::FETCH_ASSOC);

            if (!$image) {
                $this->pdo->commit();
                return false;
            }

            $deleteStmt = $this->pdo->prepare(
                "DELETE FROM product_images WHERE id = :id"
            );
            $deleteStmt->execute([':id' => $id]);

            if ((int) $image['is_primary'] === 1) {
                $nextStmt = $this->pdo->prepare(
                    "SELECT id
     FROM product_images
     WHERE product_id = :product_id
     ORDER BY sort_order ASC, id ASC
     LIMIT 1
     FOR UPDATE"
                );
                $nextStmt->execute([
                    ':product_id' => $image['product_id']
                ]);
                $nextImageId = $nextStmt->fetchColumn();

                if ($nextImageId !== false) {
                    $setPrimaryStmt = $this->pdo->prepare(
                        "UPDATE product_images SET is_primary = 1 WHERE id = :id"
                    );
                    $setPrimaryStmt->execute([':id' => $nextImageId]);
                }
            }

            $this->pdo->commit();

            $this->deleteStoredFile($image['image_path']);

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function validateUploadedFile(array $file): string
    {
        if (!isset($file['error'], $file['tmp_name'], $file['size'])) {
            throw new InvalidArgumentException('Image file is required.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Image upload failed.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Invalid uploaded image.');
        }

        if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Image must be smaller than 5 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mimeType])) {
            throw new InvalidArgumentException(
                'Only JPEG, PNG and WebP images are allowed.'
            );
        }

        return $allowedTypes[$mimeType];
    }

    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->uploadDirectory)) {
            if (!mkdir($this->uploadDirectory, 0755, true) && !is_dir($this->uploadDirectory)) {
                throw new RuntimeException('Could not create the image upload directory.');
            }
        }

        if (!is_writable($this->uploadDirectory)) {
            throw new RuntimeException('The image upload directory is not writable.');
        }
    }

    private function deleteStoredFile(string $relativePath): void
    {
        if (filter_var($relativePath, FILTER_VALIDATE_URL)) {
            return;
        }

        $fileName = basename(str_replace('\\', '/', $relativePath));

        // Only remove files created by this class inside uploads/products.
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/i', $fileName)) {
            return;
        }

        $absolutePath = $this->uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($absolutePath) && !unlink($absolutePath)) {
            error_log('Could not remove product image: ' . $absolutePath);
        }
    }
}
