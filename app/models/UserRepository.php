<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $db) {}

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT u.id, u.name, u.email, u.password_hash, u.status, r.name AS role FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function emailTakenByOther(int $id, string $email): bool
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
        $statement->execute(['email' => $email, 'id' => $id]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function updateProfile(int $id, string $name, string $email, ?string $passwordHash): bool
    {
        if ($passwordHash === null) {
            $statement = $this->db->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id AND status = \'active\'');
            $statement->execute(['name' => $name, 'email' => $email, 'id' => $id]);
        } else {
            $statement = $this->db->prepare('UPDATE users SET name = :name, email = :email, password_hash = :password_hash WHERE id = :id AND status = \'active\'');
            $statement->execute(['name' => $name, 'email' => $email, 'password_hash' => $passwordHash, 'id' => $id]);
        }
        return $statement->rowCount() === 1;
    }

    public function allMembers(): array
    {
        $statement = $this->db->prepare("SELECT u.id, u.name, u.email, u.created_at FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.name = 'member' AND u.status = 'active' ORDER BY u.created_at DESC");
        $statement->execute();
        return $statement->fetchAll() ?: [];
    }
}