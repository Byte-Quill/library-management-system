<?php
declare(strict_types=1);

final class AuditLogService
{
    public function __construct(private AuditLogRepository $logs) {}

    public function page(int $page): array
    {
        $perPage = 25; $total = $this->logs->count(); $pages = max(1, (int) ceil($total / $perPage)); $page = min(max(1, $page), $pages);
        return ['logs' => $this->logs->page($perPage, ($page - 1) * $perPage), 'page' => $page, 'pages' => $pages, 'total' => $total];
    }
}