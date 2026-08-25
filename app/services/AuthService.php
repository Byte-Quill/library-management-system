<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(private PDO $db, private ?AuditService $audits = null)
    {
    }

    public function register(string $name, string $email, string $password): bool
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || strlen($password) > 4096) {
            throw new InvalidArgumentException('Please provide valid details. Passwords must contain at least 8 characters.');
        }

        $role = $this->db->query("SELECT id FROM roles WHERE name = 'member'")->fetchColumn();
        if (!$role) {
            throw new RuntimeException('Member role is not configured.');
        }

        $existing = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetchColumn() !== false) {
            throw new InvalidArgumentException('An account with that email already exists.');
        }

        $statement = $this->db->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (:role_id, :name, :email, :password_hash)');
        try {
            $created = $statement->execute([
                'role_id' => $role,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $exception) {
            // Unique-constraint violation from a concurrent registration.
            if ($exception->getCode() === '23000') {
                throw new InvalidArgumentException('An account with that email already exists.');
            }
            throw $exception;
        }
        if ($created) $this->audits?->record(null, 'user_registered', 'user', (int) $this->db->lastInsertId());
        return $created;
    }

    public function createMember(string $name, string $email, string $password): bool
    {
        return $this->register($name, $email, $password);
    }

    public function attempt(string $email, string $password): ?array
    {
        $statement = $this->db->prepare('SELECT u.id, u.name, u.email, u.password_hash, r.name AS role FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.email = :email AND u.status = \'active\' LIMIT 1');
        $statement->execute(['email' => strtolower($email)]);
        $user = $statement->fetch();

        // Always run password_verify (even for unknown emails) to keep the
        // response timing consistent and slow down account enumeration.
        $hash = $user['password_hash'] ?? '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';
        if (!$user || !password_verify($password, $hash)) {
            $this->audits?->record(null, 'login_failed', 'user', isset($user['id']) ? (int) $user['id'] : null);
            return null;
        }

        // Transparently upgrade hashes when the algorithm or cost changes.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id')->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }

        $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);
        unset($user['password_hash']);
        $this->audits?->record((int) $user['id'], 'login_succeeded', 'user', (int) $user['id']);
        return $user;
    }
}
