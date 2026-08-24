<?php
declare(strict_types=1);

final class AuditService
{
    public function __construct(private AuditRepository $audits)
    {
    }

    public function record(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        try { $this->audits->record($userId, $action, $entityType, $entityId, $metadata); } catch (Throwable $exception) { error_log($exception->__toString()); }
    }
}