<?php

namespace App\Services\Analytics;

use App\Models\Product;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Illuminate\Support\Collection;

class ProductsMetricsService implements ProductsMetricsInterface
{
    /**
     * Get an overview of product sales metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
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

    /**
     * Retrieve product sales data for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getProductSalesData(string $startDate, string $endDate): Collection
    {
        return Product::whereHas('sales', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sales.date', [$startDate, $endDate]);
        })->with(['sales' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sales.date', [$startDate, $endDate]);
        }])->get();
    }

    /**
     * Get the top-selling products based on the provided product data.
     *
     * @param Collection $products
     * @return array
     */
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

    /**
     * Get the least-selling products based on the provided product data.
     *
     * @param Collection $products
     * @return array
     */
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

    /**
     * Get products that generate the highest revenue based on the provided product data.
     *
     * @param Collection $products
     * @return array
     */
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

    /**
     * Get products that generate the lowest revenue based on the provided product data.
     *
     * @param Collection $products
     * @return array
     */
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

    /**
     * Get detailed product metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDetailedMetrics(string $startDate, string $endDate): array
    {
        $products = $this->getProducts($startDate, $endDate);

        return $products->map(function ($product) use ($startDate, $endDate) {
            return $this->calculateProductMetrics($product, $startDate, $endDate);
        })->toArray();
    }

    /**
     * Retrieve products with their related sales for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getProducts(string $startDate, string $endDate): Collection
    {
        return Product::with([
            'sales' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('sales.date', [$startDate, $endDate]);
            }
        ])->orderByDesc('category_id')->get();
    }

    /**
     * Calculate specific metrics for a product for the specified date range.
     *
     * @param Product $product
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
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

    /**
     * Get the total quantity sold for a product.
     *
     * @param Product $product
     * @return int
     */
    public function calculateTotalQuantitySold(Product $product): int
    {
        return $product->sales()->sum('quantity');
    }

    /**
     * Get the total sales revenue for a product.
     *
     * @param Product $product
     * @return float
     */
    public function calculateTotalSalesRevenue(Product $product): float
    {
        return $product->sales()->sum('total_sale') / 100;
    }

    /**
     * Get the stock balance for a product at a specific date.
     *
     * @param Product $product
     * @param string $date
     * @return int
     */
    public function getStockBalanceAt(Product $product, string $date): int
    {
        return $product->inventoryTransactions()
            ->where('date', '<=', $date)
            ->orderByDesc('date')
            ->value('stock_balance') ?? 0;
    }

    /**
     * Get specific metrics for a specific product for the specified date range.
     *
     * @param Product $product
     * @param string $startDate
     * @param string $endDate
     * @return array
     * @throws Exception
     */
    public function getProductSpecificMetrics(Product $product, string $startDate, string $endDate): array
    {
        $dateRange = $this->generateDateRange($startDate, $endDate);

        $quantitySoldSeries = [];
        $salesRevenueSeries = [];
        $stockBalanceSeries = [];

        foreach ($dateRange as $date) {
            $dailyQuantitySold = $this->calculateProductQuantitySold($product, $date);
            $dailySalesRevenue = $this->calculateProductSalesRevenue($product, $date);
            $stockBalance = $this->getStockBalanceAt($product, $date);

            $quantitySoldSeries[] = [
                'date' => $date,
                'amount' => $dailyQuantitySold,
            ];

            $salesRevenueSeries[] = [
                'date' => $date,
                'amount' => $dailySalesRevenue,
            ];

            $stockBalanceSeries[] = [
                'date' => $date,
                'amount' => $stockBalance,
            ];
        }

        return [
            'quantity_sold' => $quantitySoldSeries,
            'sales_revenue' => $salesRevenueSeries,
            'stock_balance' => $stockBalanceSeries,
        ];
    }


    /**
     * Generate a date range array given specific start and end dates.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     * @throws Exception
     */
    private function generateDateRange(string $startDate, string $endDate): array
    {
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );

        return array_map(
            fn($date) => $date->format('Y-m-d'),
            iterator_to_array($period)
        );
    }

    /**
     * Calculate the quantity sold for a product at a specific date.
     *
     * @param Product $product
     * @param string $date
     * @return int
     */
    public function calculateProductQuantitySold(Product $product, string $date): int
    {
        return $product->sales()
            ->whereDate('sales.date', $date)
            ->sum('quantity') ?: 0;
    }

    /**
     * Calculate the sales revenue for a product at a specific date.
     *
     * @param Product $product
     * @param string $date
     * @return float
     */
    public function calculateProductSalesRevenue(Product $product, string $date): float
    {
        return $product->sales()
            ->whereDate('sales.date', $date)
            ->sum('total_sale') / 100 ?: 0;
    }
}
