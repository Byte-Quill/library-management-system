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
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512) ?: null,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }
}