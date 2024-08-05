<?php
namespace App\Services\Analytics;

interface StockMetricsInterface
{
    public function getOverviewMetrics(): array;

    public function getDetailedMetrics(): array;
}
