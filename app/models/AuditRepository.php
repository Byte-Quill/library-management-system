<?php
declare(strict_types=1);

final class AuditRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function record(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        $statement = $this->db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata) VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent, :metadata)');
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            'user_agent' => substr(preg_replace('/[\x00-\x1F\x7F]/', '', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')) ?? '', 0, 512) ?: null,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    public function page(int $limit, int $offset): array
    {
        $statement = $this->db->prepare('SELECT a.id, a.action, a.entity_type, a.entity_id, a.ip_address, a.user_agent, a.metadata, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    }
}