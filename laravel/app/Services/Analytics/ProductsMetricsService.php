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
     * @param string $startDate
     * @param $endDate
     * @return array
     */
    public function getOverviewMetrics(string $startDate, $endDate): array
    {
        $products = $this->getProductSalesData($startDate, $endDate);

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

    private function getProductSalesData(string $startDate, string $endDate): Collection
    {
        return Product::with(['sales' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sales.date', [$startDate, $endDate]);
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

    public function getDetailedMetrics(string $startDate, string $endDate): array
    {
        $products = $this->getProducts($startDate, $endDate);
        return $this->calculateDetailedMetrics($products, $startDate, $endDate);
    }

    private function getProducts(string $startDate, string $endDate): Collection
    {
        return Product::with([
            'sales' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('sales.date', [$startDate, $endDate]);
            }
        ])->orderByDesc('category_id')->get();
    }

    private function calculateDetailedMetrics(Collection $products, string $startDate, string $endDate): array
    {
        return $products->map(function ($product) use ($startDate, $endDate) {
            $totalQuantitySold = $product->sales->sum('pivot.quantity');
            $totalSalesRevenue = $product->sales->sum('pivot.total_sale');

            $initialStock = $this->getStockBalanceAt($product, $startDate);
            $finalStock = $this->getStockBalanceAt($product, $endDate);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name,
                'provider' => $product->provider->name,
                'sale' => $product->sale / 100,
                'total_quantity_sold' => $totalQuantitySold,
                'total_sales_revenue' => $totalSalesRevenue / 100,
                'initial_stock_balance' => $initialStock,
                'final_stock_balance' => $finalStock,
            ];
        })->toArray();
    }

    private function getStockBalanceAt(Product $product, string $date): int
    {
        return $product->inventoryTransactions()
            ->where('date', '<=', $date)
            ->orderByDesc('date')
            ->value('stock_balance') ?? 0;
    }
}
