<?php
declare(strict_types=1);

final class AuthorRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query("SELECT id, name, status FROM authors WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    }

    public function create(string $name): bool
    {
        return $this->db->prepare("INSERT INTO authors (name, status) VALUES (:name, 'active')")->execute(['name' => $name]);
    }

    public function archive(int $id): bool
    {
        $statement = $this->db->prepare("UPDATE authors SET status = 'archived' WHERE id = :id AND status = 'active'");
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }
}