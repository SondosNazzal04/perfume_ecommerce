<?php

class Category
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $sql = "SELECT
                    c.id,
                    c.group_id,
                    cg.name AS group_name,
                    c.name,
                    c.slug,
                    c.created_at
                FROM categories c
                JOIN category_groups cg ON cg.id = c.group_id
                ORDER BY c.id DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    c.id,
                    c.group_id,
                    cg.name AS group_name,
                    c.name,
                    c.slug,
                    c.created_at
                FROM categories c
                JOIN category_groups cg ON cg.id = c.group_id
                WHERE c.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }

    public function addCategory(
        int $groupId,
        string $name,
        string $slug
    ): bool {
        $sql = "INSERT INTO categories (group_id, name, slug)
                VALUES (:group_id, :name, :slug)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':group_id' => $groupId,
            ':name' => trim($name),
            ':slug' => trim($slug)
        ]);
    }

    public function updateCategory(
        int $id,
        int $groupId,
        string $name,
        string $slug
    ): bool {
        $sql = "UPDATE categories
                SET group_id = :group_id,
                    name = :name,
                    slug = :slug
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':group_id' => $groupId,
            ':name' => trim($name),
            ':slug' => trim($slug)
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        $sql = "DELETE FROM categories
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}
