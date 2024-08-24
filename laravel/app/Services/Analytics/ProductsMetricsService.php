<?php

namespace App\Services\Analytics;

use App\Models\Product;
use Illuminate\Support\Collection;

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

        $topSellers = $this->getTopSellingProducts($products);
        $leastSellers = $this->getLeastSellingProducts($products);
        $highestRevenueProducts = $this->getHighestRevenueProducts($products);
        $lowestRevenueProducts = $this->getLowestRevenueProducts($products);

        return [
            'top_selling_products' => $topSellers,
            'least_selling_products' => $leastSellers,
            'highest_revenue_products' => $highestRevenueProducts,
            'lowest_revenue_products' => $lowestRevenueProducts,
        ];
    }

    public function getProductSalesData(string $startDate, string $endDate): Collection
    {
        return Product::whereHas('sales', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sales.date', [$startDate, $endDate]);
        })->with(['sales' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sales.date', [$startDate, $endDate]);
        }])->get();
    }

    public function getTopSellingProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => $product->sales()->sum('quantity') ?: 0
            ];
        })->sortByDesc('quantity')->take(5)->values()->toArray();
    }

    public function getLeastSellingProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => $product->sales()->sum('quantity') ?: 0
            ];
        })->sortBy('quantity')->take(5)->values()->toArray();
    }

    public function getHighestRevenueProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'revenue' => $product->sales()->sum('total_sale') ?: 0
            ];
        })->sortByDesc('revenue')->take(5)->values()->toArray();
    }

    public function getLowestRevenueProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'revenue' => $product->sales()->sum('total_sale') ?: 0
            ];
        })->sortBy('revenue')->take(5)->values()->toArray();
    }

    public function getDetailedMetrics(string $startDate, string $endDate): array
    {
        $products = $this->getProducts($startDate, $endDate);

        return $products->map(function ($product) use ($startDate, $endDate) {
            return $this->calculateProductMetrics($product, $startDate, $endDate);
        })->toArray();
    }

    public function getProducts(string $startDate, string $endDate): Collection
    {
        return Product::with([
            'sales' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('sales.date', [$startDate, $endDate]);
            }
        ])->orderByDesc('category_id')->get();
    }

    protected function calculateProductMetrics(Product $product, string $startDate, string $endDate): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category->name,
            'provider' => $product->provider->name,
            'sale' => $product->sale / 100,
            'total_quantity_sold' => $this->calculateTotalQuantitySold($product),
            'total_sales_revenue' => $this->calculateTotalSalesRevenue($product),
            'initial_stock_balance' => $this->getStockBalanceAt($product, $startDate),
            'final_stock_balance' => $this->getStockBalanceAt($product, $endDate),
        ];
    }

    public function calculateTotalQuantitySold(Product $product): int
    {
        return $product->sales()->sum('quantity');
    }

    public function calculateTotalSalesRevenue(Product $product): float
    {
        return $product->sales()->sum('total_sale') / 100;
    }

    public function getStockBalanceAt(Product $product, string $date): int
    {
        return $product->inventoryTransactions()
            ->where('date', '<=', $date)
            ->orderByDesc('date')
            ->value('stock_balance') ?? 0;
    }
}
