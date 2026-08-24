<?php
declare(strict_types=1);

final class CategoryRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query("SELECT id, name, status FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    }

    public function create(string $name): bool
    {
        $statement = $this->db->prepare("INSERT INTO categories (name, status) VALUES (:name, 'active')");
        return $statement->execute(['name' => $name]);
    }

    public function archive(int $id): bool
    {
        $statement = $this->db->prepare("UPDATE categories SET status = 'archived' WHERE id = :id AND status = 'active'");
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }
}