<?php
declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        $user = AuthorizationMiddleware::requireAuthentication();
        require dirname(__DIR__) . '/views/dashboard/index.php';
    }
}