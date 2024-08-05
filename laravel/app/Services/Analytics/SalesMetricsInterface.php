<?php

namespace App\Services\Analytics;

interface SalesMetricsInterface
{
    public function getMetrics(string $period): array;
}
