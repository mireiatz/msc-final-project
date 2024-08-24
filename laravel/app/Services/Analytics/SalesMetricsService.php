<?php
namespace App\Services\Analytics;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesMetricsService implements SalesMetricsInterface
{
    /**
     * Get sales analytics for the specified period.
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

    public function getSales(string $startDate, string $endDate): Collection
    {
        return Sale::whereBetween('sales.date', [$startDate, $endDate])
            ->with('products')
            ->get();
    }

    public function countSales($sales): int
    {
        return $sales->count();
    }

    public function getHighestSale($sales): int
    {
        return $sales->max('sale') / 100;
    }

    public function getLowestSale($sales): int
    {
        return $sales->min('sale') / 100;
    }

    public function calculateTotalItemsSold($sales): int
    {
        return $sales->sum(function ($sale) {
            return $sale->products()->sum('quantity');
        });
    }

    public function calculateTotalSalesValue($sales): int
    {
        return $sales->sum('sale') /100;
    }

    public function getMaxItemsSoldInSale($sales): int
    {
        return $sales->map(function ($sale) {
            return $sale->products()->sum('quantity');
        })->max() ?? 0;
    }

    public function getMinItemsSoldInSale($sales): int
    {
        return $sales->map(function ($sale) {
            return $sale->products()->sum('quantity');
        })->min() ?? 0;
    }

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

    public function getSalesGroupedByDate(string $startDate, string $endDate): Collection
    {
        return Sale::whereBetween('date', [$startDate, $endDate])
            ->with('products.category')
            ->get()
            ->groupBy(function ($sale) {
                return Carbon::parse($sale->date)->format('Y-m-d');
            });
    }

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
