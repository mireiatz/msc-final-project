<?php
namespace App\Services\Analytics;

interface OverviewMetricsInterface
{
    /**
     * Get overview metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getMetrics(string $startDate, string $endDate): array;
}
