<?php
declare(strict_types=1);

final class AuditController
{
    public function __construct(private AuditService $audits)
    {
    }

    public function index(): void
    {
        AuthorizationMiddleware::requireRole(['administrator']);
        $audit = $this->audits->page((int) ($_GET['page'] ?? 1));
        require dirname(__DIR__) . '/views/audit/index.php';
    }
}
