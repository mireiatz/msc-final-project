<?php
namespace App\Services\Analytics;

interface StockMetricsInterface
{
    /**
     * Get an overview of stock metrics.
     *
     * @return array
     */
    public function getOverviewMetrics(): array;

    /**
     * Get detailed stock metrics.
     *
     * @return array
     */
    public function getDetailedMetrics(): array;
}
