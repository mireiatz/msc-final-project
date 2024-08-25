<?php
namespace App\Services\Analytics;

use App\Models\Product;

interface ProductsMetricsInterface
{
    public function getOverviewMetrics(string $startDate, string $endDate): array;

    public function getDetailedMetrics(string $startDate, string $endDate): array;

    public function getProductSpecificMetrics(Product $product, string $startDate, string $endDate): array;

}
