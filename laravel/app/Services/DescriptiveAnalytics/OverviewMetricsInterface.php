<?php
namespace App\Services\DescriptiveAnalytics;

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
