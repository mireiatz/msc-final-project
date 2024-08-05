<?php

namespace App\Services\Analytics;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ProductsMetricsService implements ProductsMetricsInterface
{
    /**
     * Get product analytics for the specified period.
     *
     * @param string $period
     * @return array
     */
    public function getMetrics(string $period): array
    {
        $startDate = $this->determineStartDate($period);

        $products = $this->getProductSalesData($startDate);

        $topSellers = $this->calculateTopSellingProducts($products);
        $leastSellers = $this->calculateLeastSellingProducts($products);
        $highestRevenueProducts = $this->calculateHighestRevenueProducts($products);
        $lowestRevenueProducts = $this->calculateLowestRevenueProducts($products);

        return [
            'top_selling_products' => $topSellers,
            'least_selling_products' => $leastSellers,
            'highest_revenue_products' => $highestRevenueProducts,
            'lowest_revenue_products' => $lowestRevenueProducts,
        ];
    }

    private function getProductSalesData(string $startDate): Collection
    {
        return Product::with(['sales' => function ($query) use ($startDate) {
            $query->where('sales.date', '>=', $startDate);
        }])->get();
    }

    private function calculateTopSellingProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'name' => $product->name,
                'quantity' => $product->sales ? $product->sales->sum('pivot.quantity') : 0
            ];
        })->sortByDesc('quantity')->take(5)->pluck('name')->toArray();
    }

    private function calculateLeastSellingProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'name' => $product->name,
                'quantity' => $product->sales ? $product->sales->sum('pivot.quantity') : 0
            ];
        })->sortBy('quantity')->take(5)->pluck('name')->toArray();
    }

    private function calculateHighestRevenueProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'name' => $product->name,
                'revenue' => $product->sales ? $product->sales->sum('pivot.total_sale') : 0
            ];
        })->sortByDesc('revenue')->take(5)->pluck('name')->toArray();
    }

    private function calculateLowestRevenueProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'name' => $product->name,
                'revenue' => $product->sales ? $product->sales->sum('pivot.total_sale') : 0
            ];
        })->sortBy('revenue')->take(5)->pluck('name')->toArray();
    }

    private function determineStartDate(string $period): Carbon
    {
        return match ($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => throw new InvalidArgumentException("Invalid period: $period"),
        };
    }
}
