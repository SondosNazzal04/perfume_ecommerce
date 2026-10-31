<?php

class CategoryGroup
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, name, slug, created_at
                FROM category_groups
                ORDER BY id DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, name, slug, created_at
                FROM category_groups
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        return $group ?: null;
    }

    public function addGroup(string $name, string $slug): bool
    {
        $sql = "INSERT INTO category_groups (name, slug)
                VALUES (:name, :slug)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':name' => trim($name),
            ':slug' => trim($slug)
        ]);
    }

    public function updateGroup(int $id, string $name, string $slug): bool
    {
        $sql = "UPDATE category_groups
                SET name = :name,
                    slug = :slug
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($name),
            ':slug' => trim($slug)
        ]);
    }

    public function deleteGroup(int $id): bool
    {
        $sql = "DELETE FROM category_groups WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }
}
