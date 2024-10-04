<?php

namespace App\Services\DescriptiveAnalytics;

use App\Models\Category;
use App\Models\Product;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductsMetricsService implements ProductsMetricsInterface
{
    /**
     * Get an overview of product sales metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOverviewMetrics(string $startDate, string $endDate): array
    {
        // Aggregate total quantity and revenue for all products within the date range
        $salesMetrics = Product::select('products.id', 'products.name',
            DB::raw('SUM(sale_products.quantity) as total_quantity'),
            DB::raw('SUM(sale_products.total_sale) as total_revenue'))
            ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
            ->join('sales', 'sales.id', '=', 'sale_products.sale_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name')
            ->get();

        return [
            'top_selling_products' => $salesMetrics->sortByDesc('total_quantity')->take(5)->values()->toArray(),
            'least_selling_products' => $salesMetrics->sortBy('total_quantity')->take(5)->values()->toArray(),
            'highest_revenue_products' => $salesMetrics->sortByDesc('total_revenue')->take(5)->values()->toArray(),
            'lowest_revenue_products' => $salesMetrics->sortBy('total_revenue')->take(5)->values()->toArray(),
        ];
    }

    /**
     * Get detailed product metrics for products in a specific category within the date range.
     *
     * @param Category $category
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDetailedMetrics(Category $category, string $startDate, string $endDate): array
    {
        // Fetch products for the selected category
        $products = $this->getProductsByCategory($category, $startDate, $endDate);

        // Calculate metrics for each product
        return $products->map(function ($product) use ($startDate, $endDate) {
            return $this->calculateProductMetrics($product, $startDate, $endDate);
        })->toArray();
    }

    /**
     * Get products and their sales data for a specific category and date range.
     *
     * @param Category $category
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getProductsByCategory(Category $category, string $startDate, string $endDate): Collection
    {
        return Product::where('category_id', $category->id)
            ->with(['sales' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('sales.date', [$startDate, $endDate]);
            }])->get();
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
        // Fetch total quantity sold, total sales revenue, and stock balances
        $salesMetrics = $this->getProductSalesMetrics($product->id, $startDate, $endDate);
        $stockBalances = $this->getProductStockBalances($product, $startDate, $endDate);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category->name,
            'provider' => $product->provider->name,
            'sale' => $product->sale / 100,
            'total_quantity_sold' => $salesMetrics['total_quantity_sold'],
            'total_sales_revenue' => $salesMetrics['total_sales_revenue'],
            'initial_stock_balance' => $stockBalances['initial_stock_balance'],
            'final_stock_balance' => $stockBalances['final_stock_balance'],
        ];
    }

    /**
     * Get total quantity sold and total sales revenue for a product within a date range.
     *
     * @param string $productId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProductSalesMetrics(string $productId, string $startDate, string $endDate): array
    {
        // Aggregate sales data per quantity and revenue
        $salesData = Product::select(
            DB::raw('SUM(sale_products.quantity) as total_quantity_sold'),
            DB::raw('SUM(sale_products.total_sale) as total_sales_revenue')
        )
            ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
            ->join('sales', 'sales.id', '=', 'sale_products.sale_id')
            ->where('products.id', $productId)
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->first();

        return [
            'total_quantity_sold' => $salesData->total_quantity_sold ?: 0,
            'total_sales_revenue' => $salesData->total_sales_revenue / 100 ?: 0,
        ];
    }

    /**
     * Get the stock balance for a product at the start and end of a date range.
     *
     * @param Product $product
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProductStockBalances(Product $product, string $startDate, string $endDate): array
    {
        // Fetch stock balance at both start and end dates in a single query
        $stockBalances = $product->inventoryTransactions()
            ->whereIn('date', [$startDate, $endDate])
            ->orderByDesc('date')
            ->pluck('stock_balance', 'date');

        return [
            'initial_stock_balance' => $stockBalances->get($startDate, 0),
            'final_stock_balance' => $stockBalances->get($endDate, 0),
        ];
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
        // Generate date range for daily calculations
        $dateRange = $this->generateDateRange($startDate, $endDate);

        // Get all sales and stock balances for each date within the range
        $salesData = $product->sales()
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($sale) {
                return $sale->date->format('Y-m-d');
            });
        $stockBalances = $this->getStockBalanceInRange($product, $startDate, $endDate);

        // Loop through each date to store daily metrics
        $quantitySoldSeries = [];
        $salesRevenueSeries = [];
        $stockBalanceSeries = [];
        foreach ($dateRange as $date) {
            // Arrange sales details for the current date using pre-fetched sales data
            $dailySales = $salesData->get($date) ?? collect([]);
            $dailyQuantitySold = $dailySales->sum('sale_products.quantity');
            $dailySalesRevenue = $dailySales->sum('sale_products.total_sale') / 100;

            $quantitySoldSeries[] = [
                'date' => $date,
                'amount' => $dailyQuantitySold,
            ];

            $salesRevenueSeries[] = [
                'date' => $date,
                'amount' => $dailySalesRevenue,
            ];

            /// Arrange pre-fetched stock balance
            $stockBalanceSeries[] = [
                'date' => $date,
                'amount' => $stockBalances[$date] ?? 0,
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
        // Generate all dates between the start and end dates
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );

        // Convert the DatePeriod into an array of formatted date strings
        return array_map(
            fn($date) => $date->format('Y-m-d'),
            iterator_to_array($period)
        );
    }

    /**
     * Get the stock balance for a product over a specific date range.
     *
     * @param Product $product
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStockBalanceInRange(Product $product, string $startDate, string $endDate): array
    {
        // Get inventory transactions for the date range
        return $product->inventoryTransactions()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->date->format('Y-m-d');
            })
            ->mapWithKeys(function ($transactions, $date) { // Map each group of transactions by date
                return [$date => $transactions->last()->stock_balance];
            })
            ->toArray();
    }
}
