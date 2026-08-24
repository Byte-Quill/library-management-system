<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/middleware/AuthorizationMiddleware.php';

final class DashboardController
{
    public function index(): void
    {
        $user = AuthorizationMiddleware::requireAuthentication();
        require dirname(__DIR__) . '/views/dashboard/index.php';
    }
}