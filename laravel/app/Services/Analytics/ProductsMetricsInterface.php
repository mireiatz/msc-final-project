<?php
namespace App\Services\Analytics;

interface ProductsMetricsInterface
{
    public function getMetrics(string $period): array;
}
