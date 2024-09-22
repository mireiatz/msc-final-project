<?php
namespace App\Services\Analytics;

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
        $sales = $this->getSales($startDate, $endDate);

        $salesCount = $this->countSales($sales);
        $highestSale = $this->getHighestSale($sales);
        $lowestSale = $this->getLowestSale($sales);
        $totalItemsSold = $this->calculateTotalItemsSold($sales);
        $totalSalesValue = $this->calculateTotalSalesValue($sales);
        $maxItemsSoldInSale = $this->getMaxItemsSoldInSale($sales);
        $minItemsSoldInSale = $this->getMinItemsSoldInSale($sales);

        return [
            'sales_count' => $salesCount,
            'highest_sale' => $highestSale,
            'lowest_sale' => $lowestSale,
            'total_items_sold' => $totalItemsSold,
            'total_sales_value' => $totalSalesValue,
            'max_items_sold_in_sale' => $maxItemsSoldInSale,
            'min_items_sold_in_sale' => $minItemsSoldInSale,
        ];
    }

    /**
     * Get sales data for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getSales(string $startDate, string $endDate): Collection
    {
        return Sale::whereBetween('sales.date', [$startDate, $endDate])
            ->with('products')
            ->get();
    }

    /**
     * Count the total number of sales for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function countSales(Collection $sales): int
    {
        return $sales->count();
    }

    /**
     * Get the highest sale value for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function getHighestSale(Collection $sales): int
    {
        return $sales->max('sale') / 100;
    }

    /**
     * Get the lowest sale value for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function getLowestSale(Collection $sales): int
    {
        return $sales->min('sale') / 100;
    }

    /**
     * Calculate the total number of items sold for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function calculateTotalItemsSold(Collection $sales): int
    {
        return $sales->sum(function ($sale) {
            return $sale->products()->sum('quantity');
        });
    }

    /**
     * Calculate the total sales value for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function calculateTotalSalesValue(Collection $sales): int
    {
        return $sales->sum('sale') / 100;
    }

    /**
     * Get the maximum number of items sold in a single sale for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function getMaxItemsSoldInSale(Collection $sales): int
    {
        return $sales->map(function ($sale) {
            return $sale->products()->sum('quantity');
        })->max() ?? 0;
    }

    /**
     * Get the minimum number of items sold in a single sale for the provided sales data.
     *
     * @param Collection $sales
     * @return int
     */
    public function getMinItemsSoldInSale(Collection $sales): int
    {
        return $sales->map(function ($sale) {
            return $sale->products()->sum('quantity');
        })->min() ?? 0;
    }

    /**
     * Get detailed sales metrics for the specified date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDetailedMetrics(string $startDate, string $endDate): array
    {
        $sales = $this->getSalesGroupedByDate($startDate, $endDate);

        $allSales = $this->mapAllSales($sales);
        $salesPerCategory = $this->mapSalesPerCategory($sales);
        $salesPerProduct = $this->mapSalesPerProduct($sales);

        return [
            'all_sales' => $allSales,
            'sales_per_category' => $salesPerCategory,
            'sales_per_product' => $salesPerProduct,
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
            ->groupBy(function ($sale) {
                return Carbon::parse($sale->date)->format('Y-m-d');
            });
    }

    /**
     * Map all sales data to an array format.
     *
     * @param Collection $sales
     * @return array
     */
    public function mapAllSales(Collection $sales): array
    {
        return $sales->map(function ($sales) {
                return [
                    'date' => Carbon::parse($sales->first()->date)->format('Y-m-d'),
                    'total_sale' => $sales->sum('sale') / 100,
                    'items' => $sales->sum(function ($sale) {
                        return $sale->products()->sum('quantity');
                    }),
                ];
            })->values()->toArray();
    }


    /**
     * Map sales data per product to an array format, grouped by product and date.
     *
     * @param Collection $sales
     * @return array
     */
    public function mapSalesPerProduct(Collection $sales): array
    {
        return $sales->flatMap(function ($salesOnDay) {
            return $salesOnDay->flatMap(function ($sale) {
                return $sale->products->map(function ($product) use ($sale) {
                    return [
                        'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $product->sale_products->quantity,
                        'total_sale' => $product->sale_products->total_sale,
                    ];
                });
            });
        })->groupBy('product_id')->flatMap(function ($productSales) {
            return $productSales->groupBy('date')->map(function ($salesOnDate) {
                return [
                    'date' => $salesOnDate->first()['date'],
                    'product_id' => $salesOnDate->first()['product_id'],
                    'product_name' => $salesOnDate->first()['product_name'],
                    'quantity' => $salesOnDate->sum('quantity'),
                    'total_sale' => $salesOnDate->sum('total_sale') / 100,
                ];
            })->values()->toArray();
        })->values()->toArray();
    }

    /**
     * Map sales data per category to an array format, grouped by category and date.
     *
     * @param Collection $sales
     * @return array
     */
    public function mapSalesPerCategory(Collection $sales): array
    {
        return $sales->flatMap(function ($salesOnDay) {
            return $salesOnDay->flatMap(function ($sale) {
                return $sale->products->map(function ($product) use ($sale) {
                    return [
                        'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                        'category_id' => $product->category_id,
                        'category_name' => $product->category->name,
                        'quantity' => $product->sale_products->quantity,
                        'total_sale' => $product->sale_products->total_sale,
                    ];
                });
            });
        })->groupBy('category_id')->flatMap(function ($categorySales) {
            return $categorySales->groupBy('date')->map(function ($salesOnDate) {
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
