<?php
namespace App\Services\Analytics;

interface OverviewMetricsInterface
{
    public function getOverviewMetrics(string $period): array;
}
