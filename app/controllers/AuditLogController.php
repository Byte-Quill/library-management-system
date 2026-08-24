<?php
declare(strict_types=1);

final class AuditLogController
{
    public function __construct(private AuditLogService $logs) {}

    public function index(): void
    {
        AuthorizationMiddleware::requireRole(['administrator']);
        $audit = $this->logs->page((int) ($_GET['page'] ?? 1));
        require dirname(__DIR__) . '/views/audit/index.php';
    }
}