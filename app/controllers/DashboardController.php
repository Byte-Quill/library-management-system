<?php
declare(strict_types=1);

final class DashboardController
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function index(): void
    {
        $user = AuthorizationMiddleware::requireAuthentication();
        $stats = ($user['role'] === 'member') ? $this->dashboard->member((int) $user['id']) : $this->dashboard->staff();
        require dirname(__DIR__) . '/views/dashboard/index.php';
    }
}