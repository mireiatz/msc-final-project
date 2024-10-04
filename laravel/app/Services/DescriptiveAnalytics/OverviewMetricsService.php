<?php

namespace App\Services\DescriptiveAnalytics;


readonly class OverviewMetricsService implements OverviewMetricsInterface
{
    public function __construct(
        private StockMetricsInterface    $stockMetricsInterface,
        private SalesMetricsInterface    $salesMetricsInterface,
        private ProductsMetricsInterface $productsMetricsInterface,
    )
    {}

    /**
     * Get overview metrics, including stock, sales and products metrics, for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getMetrics(string $startDate, string $endDate): array
    {
        $metrics['stock'] = $this->stockMetricsInterface->getOverviewMetrics();
        $metrics['sales'] = $this->salesMetricsInterface->getOverviewMetrics($startDate, $endDate);
        $metrics['products'] = $this->productsMetricsInterface->getOverviewMetrics($startDate, $endDate);

        return $metrics;
    }
}
