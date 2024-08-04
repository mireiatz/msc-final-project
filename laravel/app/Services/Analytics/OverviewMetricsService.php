<?php

namespace App\Services\Analytics;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OverviewMetricsService implements OverviewMetricsInterface
{
    /**
     * Get overview analytics for the specified period.
     *
     * @param string $period
     * @return array
     */
    public function getOverviewMetrics(string $period): array
    {
        $startDate = $this->determineStartDate($period);

        $products = Product::all();
        $productsInStockCount = $this->calculateProductStockCount($products, 'in_stock');
        $productsOutOfStockCount = $this->calculateProductStockCount($products, 'out_of_stock');
        $criticallyLowStockProducts = $this->findCriticallyLowStockProducts($products);
        $excessiveStockProducts = $this->findExcessiveStockProducts($products);
        $inventoryValue = $this->calculateInventoryValue($products);

        $salesSummary = $this->summariseSales($startDate);
        $highestSale = $this->findExtremeSaleAmount($startDate, 'highest');
        $lowestSale = $this->findExtremeSaleAmount($startDate, 'lowest');
        $mostItemsSold = $this->findExtremeSaleItems($startDate, 'most_items');
        $leastItemsSold = $this->findExtremeSaleItems($startDate, 'least_items');

        $topSellingProducts = $this->findTopSellers($startDate);
        $leastSellingProducts = $this->findLeastSellers($startDate);
        $highestRevenueProducts = $this->findTopRevenueProducts($startDate);
        $lowestRevenueProducts = $this->findLowestRevenueProducts($startDate);

        return [
            'stock' => [
                'products_in_stock_count' => $productsInStockCount,
                'products_out_of_stock_count' => $productsOutOfStockCount,
                'critically_low_stock_products' => $criticallyLowStockProducts,
                'excessive_stock_products' => $excessiveStockProducts,
                'inventory_value' => $inventoryValue/100,
            ],
            'sales' => [
                'number_of_sales' => $salesSummary['number'],
                'total_items_sold' => $salesSummary['items'],
                'total_sales_value' => $salesSummary['total']/100,
                'highest_sale' => $highestSale/100,
                'lowest_sale' => $lowestSale/100,
                'sale_with_most_items' => $mostItemsSold,
                'sale_with_least_items' => $leastItemsSold,
            ],
            'product_performance' => [
                'top_selling_products' => $topSellingProducts,
                'least_selling_products' => $leastSellingProducts,
                'highest_revenue_products' => $highestRevenueProducts,
                'lowest_revenue_products' => $lowestRevenueProducts,
            ],
        ];
    }

    private function calculateProductStockCount($products, string $type): int
    {
        return match ($type) {
            'in_stock' => $products->filter(function ($product) {
                return $product->stock_balance > 0;
            })->count(),
            'out_of_stock' => $products->filter(function ($product) {
                return $product->stock_balance <= 0;
            })->count(),
            default => throw new InvalidArgumentException("Invalid stock type: $type"),
        };
    }

    private function findCriticallyLowStockProducts($products): array
    {
        return $products->filter(function ($product) {
            return $product->stock_balance < $product->min_stock_level;
        })->pluck('name')->toArray();
    }

    private function findExcessiveStockProducts($products): array
    {
        return $products->filter(function ($product) {
            return $product->stock_balance > $product->max_stock_level;
        })->pluck('name')->toArray();
    }

    private function calculateInventoryValue($products): float
    {
        return $products->reduce(function ($carry, $product) {
        $stockBalance = $product->inventoryTransactions()->sum('quantity');
        $productValue = $stockBalance * $product->cost;

        return $carry + $productValue;
    }, 0.0);
    }

    private function summariseSales(string $startDate): array
    {
        $salesData = Sale::where('sales.date', '>=', $startDate)
            ->join('sale_products', 'sales.id', '=', 'sale_products.sale_id')
            ->selectRaw('COUNT(DISTINCT sales.id) as number_of_sales, SUM(sale_products.quantity) as items, SUM(sales.sale) as money')
            ->first();

        return [
            'number' => $salesData->number_of_sales ?? 0,
            'items' => $salesData->items ?? 0,
            'total' => $salesData->money ?? 0,
        ];
    }

    private function findExtremeSaleAmount(string $startDate, string $type): ?float
    {
        $query = Sale::where('sales.created_at', '>=', $startDate)
            ->select('sales.sale');

        $result = match ($type) {
            'highest' => $query->orderBy('sales.sale', 'desc')->first(),
            'lowest' => $query->orderBy('sales.sale', 'asc')->first(),
            default => throw new InvalidArgumentException("Invalid type for sale amount: $type"),
        };

        return $result ? $result->sale : null;
    }

    private function findExtremeSaleItems(string $startDate, string $type): ?int
    {
        $query = Sale::where('sales.created_at', '>=', $startDate)
            ->join('sale_products', 'sales.id', '=', 'sale_products.sale_id')
            ->select(DB::raw('SUM(sale_products.quantity) as total_items'))
            ->groupBy('sales.id');

        $result = match ($type) {
            'most_items' => $query->orderBy('total_items', 'desc')->first(),
            'least_items' => $query->orderBy('total_items', 'asc')->first(),
            default => throw new InvalidArgumentException("Invalid type for sale items: $type"),
        };

        return $result ? $result->total_items : null;
    }

    private function findTopSellers(string $startDate): array
    {
        return Product::select('products.name')
            ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
            ->join('sales', 'sales.id', '=', 'sale_products.sale_id')
            ->where('sales.created_at', '>=', $startDate)
            ->groupBy('products.id', 'products.name')
            ->orderByRaw('SUM(sale_products.quantity) DESC')
            ->take(5)
            ->pluck('name')
            ->toArray();
    }

    private function findLeastSellers(string $startDate): array
    {
        return Product::select('products.name')
            ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
            ->join('sales', 'sales.id', '=', 'sale_products.sale_id')
            ->where('sales.created_at', '>=', $startDate)
            ->groupBy('products.id', 'products.name')
            ->orderByRaw('SUM(sale_products.quantity) ASC')
            ->take(5)
            ->pluck('name')
            ->toArray();
    }

    private function findTopRevenueProducts(string $startDate): array
    {
        return Product::select('products.name', DB::raw('SUM(sale_products.total_sale) as total_revenue'))
            ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
            ->join('sales', 'sales.id', '=', 'sale_products.sale_id')
            ->where('sales.created_at', '>=', $startDate)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'desc')
            ->take(5)
            ->pluck('name')
            ->toArray();
    }

    private function findLowestRevenueProducts(string $startDate): array
    {
        return Product::select('products.name', DB::raw('SUM(sale_products.total_sale) as total_revenue'))
            ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
            ->join('sales', 'sales.id', '=', 'sale_products.sale_id')
            ->where('sales.created_at', '>=', $startDate)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'asc')
            ->take(5)
            ->pluck('name')
            ->toArray();
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
