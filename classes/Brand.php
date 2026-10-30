<?php

class Brand
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, name, description, logo, created_at
            FROM brands
            ORDER BY id DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function addBrand(
        string $name,
        ?string $description = null,
        ?string $logo = null
    ) {
        $sql = "INSERT INTO brands (name, description, logo)
            VALUES (:name, :description, :logo)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':name' => trim($name),
            ':description' => $description,
            ':logo' => $logo
        ]);
    }



    public function deleteBrand(int $id): bool
    {
        $sql = "DELETE FROM brands WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }


    public function updateBrand(int $id, string $name, ?string $description = null, ?string $logo = null): bool
    {
        $sql = "UPDATE brands
            SET name = :name,
                description = :description,
                logo = :logo
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($name),
            ':description' => $description,
            ':logo' => $logo
        ]);
    }


    public function getById(int $id): ?array
    {
        $sql = "SELECT id, name, description, logo, created_at
            FROM brands
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $brand = $stmt->fetch(PDO::FETCH_ASSOC);

        return $brand ?: null;
    }
}
