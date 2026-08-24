<?php
declare(strict_types=1);

final class AuditLogRepository
{
    public function __construct(private PDO $db) {}

    public function page(int $limit, int $offset): array
    {
        $statement = $this->db->prepare('SELECT a.id, a.action, a.entity_type, a.entity_id, a.ip_address, a.user_agent, a.metadata, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT); $statement->bindValue(':offset', $offset, PDO::PARAM_INT); $statement->execute();
        return $statement->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    }
}