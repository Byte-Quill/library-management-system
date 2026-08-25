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

    public function page(int $page): array
    {
        $perPage = 25;
        $total = $this->audits->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        return ['logs' => $this->audits->page($perPage, ($page - 1) * $perPage), 'page' => $page, 'pages' => $pages, 'total' => $total];
    }
}