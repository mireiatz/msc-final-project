<?php
namespace App\Services\DescriptiveAnalytics;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesMetricsService implements SalesMetricsInterface
{
    /**
     * Get an overview of sales metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOverviewMetrics(string $startDate, string $endDate): array
    {
        $metrics = [
            'sales_count' => 0,
            'highest_sale' => 0,
            'lowest_sale' => PHP_INT_MAX,
            'total_items_sold' => 0,
            'total_sales_value' => 0,
            'max_items_sold_in_sale' => 0,
            'min_items_sold_in_sale' => PHP_INT_MAX,
        ];

        Sale::whereBetween('date', [$startDate, $endDate])
            ->with('products')
            ->chunk(100, function ($sales) use (&$metrics) { // Chunk the data
                // Incrementally calculate metrics over each chunk
                $metrics['sales_count'] += $sales->count();
                $metrics['highest_sale'] = max($metrics['highest_sale'], $sales->max('sale') / 100);
                $metrics['lowest_sale'] = min($metrics['lowest_sale'], $sales->min('sale') / 100);
                $metrics['total_items_sold'] += $sales->sum(fn($sale) => $sale->products->sum('sale_products.quantity'));
                $metrics['total_sales_value'] += $sales->sum('sale') / 100;

                $maxItemsInSale = $sales->map(fn($sale) => $sale->products->sum('sale_products.quantity'))->max();
                $minItemsInSale = $sales->map(fn($sale) => $sale->products->sum('sale_products.quantity'))->min();

                $metrics['max_items_sold_in_sale'] = max($metrics['max_items_sold_in_sale'], $maxItemsInSale);
                $metrics['min_items_sold_in_sale'] = min($metrics['min_items_sold_in_sale'], $minItemsInSale);
            });

        // Avoid "PHP_INT_MAX" as lowest_sale and min items sold
        $metrics['lowest_sale'] = $metrics['lowest_sale'] === PHP_INT_MAX ? 0 : $metrics['lowest_sale'];
        $metrics['min_items_sold_in_sale'] = $metrics['min_items_sold_in_sale'] === PHP_INT_MAX ? 0 : $metrics['min_items_sold_in_sale'];

        return $metrics;
    }

    /**
     * Get detailed sales metrics grouped by category for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDetailedMetrics(string $startDate, string $endDate): array
    {
        // Fetch sales grouped by category for the date range
        $sales = $this->getSalesGroupedByDate($startDate, $endDate);

        // Map sales per category and overall sales data
        return [
            'all_sales' => $this->mapAllSales($sales),
            'sales_per_category' => $this->mapSalesPerCategory($sales),
        ];
    }

    /**
     * Get sales data grouped by date for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getSalesGroupedByDate(string $startDate, string $endDate): Collection
    {
        return Sale::whereBetween('date', [$startDate, $endDate])
            ->with('products.category')
            ->get()
            ->groupBy(fn($sale) => Carbon::parse($sale->date)->format('Y-m-d'));
    }

    /**
     * Map all sales data to an array format.
     *
     * @param Collection $sales
     * @return array
     */
    public function mapAllSales(Collection $sales): array
    {
        // Map for total quantities and revenue
        return $sales->map(function ($salesOnDate) {
            return [
                'date' => Carbon::parse($salesOnDate->first()->date)->format('Y-m-d'),
                'total_sale' => $salesOnDate->sum('sale') / 100,
                'items' => $salesOnDate->sum(fn($sale) => $sale->products->sum('sale_products.quantity')),
            ];
        })->values()->toArray();
    }

    /**
     * Map sales data per category grouped by date.
     *
     * @param Collection $sales
     * @return array
     */
    public function mapSalesPerCategory(Collection $sales): array
    {
        // Flatten the sales data to map sales to each product for each date
        return $sales->flatMap(function ($salesOnDate) {
            // For each sale on the date, iterate over the products
            return $salesOnDate->flatMap(function ($sale) {
                return $sale->products->map(function ($product) use ($sale) {
                    // Map the sale data for each product
                    return [
                        'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                        'category_id' => $product->category_id,
                        'category_name' => $product->category->name,
                        'quantity' => $product->sale_products->quantity,
                        'total_sale' => $product->sale_products->total_sale,
                    ];
                });
            });
        // Group the flattened data by category
        })->groupBy('category_id')->flatMap(function ($categorySales) {
            // Flatten the grouped categories and further group by date
            return $categorySales->groupBy('date')->map(function ($salesOnDate) {
                // Use the first for common details and aggregate the total quantity and total sales for the category
                return [
                    'date' => $salesOnDate->first()['date'],
                    'category_id' => $salesOnDate->first()['category_id'],
                    'category_name' => $salesOnDate->first()['category_name'],
                    'quantity' => $salesOnDate->sum('quantity'),
                    'total_sale' => $salesOnDate->sum('total_sale') / 100,
                ];
            })->values()->toArray();
        })->values()->toArray();
    }
}
