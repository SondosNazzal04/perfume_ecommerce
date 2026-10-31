<?php
class Wishlist {
    private $pdo;

    public function __construct($db_connection) {
        $this->pdo = $db_connection;
    }

    public function getAll(): array {
        $sql = 'SELECT w.user_id, w.product_id, w.created_at,
                       u.name as user_name, u.email as user_email,
                       p.name as product_name
                FROM wishlist w
                JOIN users u ON w.user_id = u.id
                JOIN products p ON w.product_id = p.id
                ORDER BY w.created_at DESC';
        try {
            $stmt = $this->pdo->run_query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function add(int $userId, int $productId): bool {
        $sql = 'INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)';
        try {
            $stmt = $this->pdo->run_query($sql, [
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getByUser(int $userId): array {
        // Fetches product details directly without a wishlist.id column
        $sql = 'SELECT p.id as product_id, p.name, p.slug, p.description, w.created_at
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = :user_id
                AND p.is_active = 1
                ORDER BY w.created_at DESC';
        try {
            $stmt = $this->pdo->run_query($sql, ['user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function remove(int $userId, int $productId): bool {
        $sql = 'DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id';
        try {
            $stmt = $this->pdo->run_query($sql, [
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
?>
