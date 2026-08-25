<?php
declare(strict_types=1);

final class DashboardService
{
    public function __construct(private DashboardRepository $dashboard) {}
    public function member(int $memberId): array { return $this->dashboard->memberStats($memberId); }
    public function staff(): array { return $this->dashboard->staffStats(); }
}