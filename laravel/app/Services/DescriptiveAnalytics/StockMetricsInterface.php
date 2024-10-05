<?php
namespace App\Services\DescriptiveAnalytics;

use App\Models\Category;

interface StockMetricsInterface
{
    /**
     * Get an overview of stock metrics.
     *
     * @return array
     */
    public function getOverviewMetrics(): array;

    /**
     * Get detailed stock metrics for a category.
     *
     * @param Category $category
     * @return array
     */
    public function getDetailedMetrics(Category $category): array;
}
