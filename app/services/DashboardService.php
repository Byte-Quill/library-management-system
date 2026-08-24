<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/models/DashboardRepository.php';

final class DashboardService
{
    public function __construct(private DashboardRepository $dashboard) {}
    public function member(int $memberId): array { return $this->dashboard->memberStats($memberId); }
    public function staff(): array { return $this->dashboard->staffStats(); }
}