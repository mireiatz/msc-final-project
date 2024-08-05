<?php

namespace App\Services\Analytics;

interface SalesMetricsInterface
{
    public function getOverviewMetrics(string $startDate, string $endDate): array;

    public function getDetailedMetrics(string $startDate, string $endDate): array;
}
