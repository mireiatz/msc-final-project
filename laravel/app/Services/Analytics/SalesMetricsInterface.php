<?php

namespace App\Services\Analytics;

interface SalesMetricsInterface
{
    /**
     * Get an overview of sales metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOverviewMetrics(string $startDate, string $endDate): array;

    /**
     * Get detailed sales metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDetailedMetrics(string $startDate, string $endDate): array;
}
