<?php
namespace App\Services\DescriptiveAnalytics;

use App\Models\Category;
use App\Models\Product;

interface ProductsMetricsInterface
{
    /**
     * Get an overview of product metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOverviewMetrics(string $startDate, string $endDate): array;

    /**
     * Get detailed product metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param Category $category
     * @return array
     */
    public function getDetailedMetrics(Category $category, string $startDate, string $endDate): array;

    /**
     * Get metrics for a specific product for the specified date range.
     *
     * @param Product $product
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProductSpecificMetrics(Product $product, string $startDate, string $endDate): array;

}
