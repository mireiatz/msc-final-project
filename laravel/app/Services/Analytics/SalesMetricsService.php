<?php
namespace App\Services\Analytics;

use App\Models\Sale;
use Carbon\Carbon;
use InvalidArgumentException;

class SalesMetricsService implements SalesMetricsInterface
{
    /**
     * Get sales analytics for the specified period.
     *
     * @param string $period
     * @return array
     */
    public function getOverviewMetrics(string $startDate, string $endDate): array
    {
        $sales = Sale::whereBetween('sales.date', [$startDate, $endDate])
            ->with('products')
            ->get();

        $numberOfSales = $this->calculateNumberOfSales($sales);
        $totalItemsSold = $this->calculateTotalItemsSold($sales);
        $totalSalesValue = $this->calculateTotalSalesValue($sales);
        $highestSale = $this->findHighestSale($sales);
        $lowestSale = $this->findLowestSale($sales);
        $mostItemsSold = $this->findSaleWithMostItems($sales);
        $leastItemsSold = $this->findSaleWithLeastItems($sales);

        return [
            'number_of_sales' => $numberOfSales,
            'total_items_sold' => $totalItemsSold,
            'total_sales_value' => $totalSalesValue,
            'highest_sale' => $highestSale / 100,
            'lowest_sale' => $lowestSale / 100,
            'sale_with_most_items' => $mostItemsSold,
            'sale_with_least_items' => $leastItemsSold,
        ];
    }

    private function calculateNumberOfSales($sales): int
    {
        return $sales->count() ?? 0;
    }

    private function calculateTotalItemsSold($sales): int
    {
        return $sales->sum(function ($sale) {
            return $sale->products()->count();
        }) ?? 0;
    }

    private function calculateTotalSalesValue($sales): int
    {
        return $sales->sum('sale') ?? 0;
    }

    private function findHighestSale($sales): int
    {
        return $sales->max('sale') ?? 0;
    }

    private function findLowestSale($sales): int
    {
        return $sales->min('sale') ?? 0;
    }

    private function findSaleWithMostItems($sales): int
    {
        return $sales->map(function ($sale) {
            return $sale->products()->count();
        })->max() ?? 0;
    }

    private function findSaleWithLeastItems($sales): int
    {
        return $sales->map(function ($sale) {
            return $sale->products()->count();
        })->min() ?? 0;
    }

    public function getDetailedMetrics(string $startDate, string $endDate): array
    {
        return [];
    }
}
