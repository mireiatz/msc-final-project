<?php
namespace App\Services\Analytics;

interface OverviewMetricsInterface
{
    public function getMetrics(string $startDate, string $endDate): array;
}
